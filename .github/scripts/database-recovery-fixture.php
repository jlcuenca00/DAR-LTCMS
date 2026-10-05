<?php

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Parcel;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

if (! app()->environment('testing') || ! str_starts_with(DB::connection()->getDatabaseName(), 'darltcms_recovery_')) {
    throw new RuntimeException('Recovery fixtures require an isolated testing database.');
}

$mode = $argv[1] ?? '';
if ($mode === 'seed') {
    $user = User::factory()->create(['username' => 'recovery.fixture', 'role' => 'staff']);
    $parcel = Parcel::create([
        'parcel_code' => 'RECOVERY-FIXTURE-001', 'municipality' => 'Dumaguete City',
        'barangay' => 'Bantayan', 'area_hectares' => 1.0000, 'status' => 'active',
    ]);
    AuditLogger::record('recovery_fixture_created', null, $parcel, ['fixture' => true], $user->id);
    exit(0);
}

if ($mode === 'empty') {
    $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename <> 'migrations'");
    if ($tables !== [] || DB::table('migrations')->count() !== 0) {
        throw new RuntimeException('Full rollback did not remove all application tables and migration entries.');
    }
    echo "Full rollback verified.\n";
    exit(0);
}

if ($mode !== 'fingerprint') {
    throw new RuntimeException('Unknown recovery fixture mode.');
}

if (Schema::hasTable('landholding_mutations') || Schema::hasColumn('land_transfer_applications', 'registry_mutated_by')
    || Schema::hasColumn('land_transfer_applications', 'registry_mutated_at')) {
    throw new RuntimeException('Out-of-scope registry mutation artifacts were restored.');
}
if (DB::table('users')->where('username', 'recovery.fixture')->count() !== 1
    || DB::table('parcels')->where('parcel_code', 'RECOVERY-FIXTURE-001')->count() !== 1
    || DB::table('audit_logs')->where('action', 'recovery_fixture_created')->count() !== 1) {
    throw new RuntimeException('Representative recovery records are missing.');
}

$fingerprint = [];
foreach (DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename") as $table) {
    $name = '"'.str_replace('"', '""', $table->tablename).'"';
    $fingerprint[$table->tablename] = DB::selectOne(
        "SELECT count(*) AS count, md5(COALESCE(string_agg(row_data::text, E'\\n' ORDER BY row_data::text), '')) AS digest FROM (SELECT to_jsonb(t) AS row_data FROM {$name} t) rows"
    );
}
$fingerprint['constraints'] = DB::select("SELECT conrelid::regclass::text AS relation, conname, pg_get_constraintdef(oid) AS definition FROM pg_constraint WHERE connamespace = 'public'::regnamespace ORDER BY relation, conname");
// PostgreSQL reparses this varchar-array enum check during restore and moves
// the text cast from the array to its elements. Accept only the two equivalent
// forms, and verify their behavior before giving them the same fingerprint.
foreach ($fingerprint['constraints'] as $constraint) {
    if ($constraint->relation !== 'required_documents' || $constraint->conname !== 'required_documents_applies_to_check') {
        continue;
    }
    $forms = [
        "CHECK (((applies_to)::text = ANY ((ARRAY['transferor'::character varying, 'transferee'::character varying])::text[])))",
        "CHECK (((applies_to)::text = ANY (ARRAY[('transferor'::character varying)::text, ('transferee'::character varying)::text])))",
    ];
    if (! in_array($constraint->definition, $forms, true)) {
        throw new RuntimeException('Unexpected required-document party constraint.');
    }
    $expression = substr($constraint->definition, 7, -1);
    foreach (['transferor' => true, 'transferee' => true, 'other' => false, '' => false] as $value => $expected) {
        $accepted = DB::selectOne("SELECT {$expression} AS accepts FROM (VALUES (?::varchar)) AS sample(applies_to)", [$value])->accepts;
        if ($accepted !== $expected) {
            throw new RuntimeException('Required-document party constraint behavior changed.');
        }
    }
    $constraint->definition = "CHECK (applies_to IN ('transferor', 'transferee'))";
}
$fingerprint['triggers'] = DB::select("SELECT tgrelid::regclass::text AS relation, tgname, pg_get_triggerdef(oid) AS definition FROM pg_trigger WHERE NOT tgisinternal AND tgrelid IN (SELECT oid FROM pg_class WHERE relnamespace = 'public'::regnamespace) ORDER BY relation, tgname");

$fingerprint['sequences'] = DB::select("SELECT sequencename, increment_by, min_value, max_value, cache_size, last_value FROM pg_sequences WHERE schemaname = 'public' ORDER BY sequencename");
$fingerprint['functions'] = DB::select("SELECT proname, pg_get_functiondef(oid) AS definition FROM pg_proc WHERE pronamespace = 'public'::regnamespace AND proname LIKE 'dar_ltcms_%' ORDER BY proname");

// Verify restored append-only behavior, not only the trigger's catalog entry.
DB::beginTransaction();
$rejected = false;
try {
    DB::table('audit_logs')->where('action', 'recovery_fixture_created')->update(['action' => 'must_not_change']);
} catch (Illuminate\Database\QueryException $exception) {
    $rejected = str_contains($exception->getMessage(), 'append-only');
} finally {
    DB::rollBack();
}
if (! $rejected) {
    throw new RuntimeException('Audit append-only protection failed after recovery.');
}
echo json_encode($fingerprint, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)."\n";
