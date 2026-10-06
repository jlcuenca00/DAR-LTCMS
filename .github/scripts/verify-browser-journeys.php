<?php

$completed = false;
// Laravel renders uncaught exceptions in these standalone bootstrapped scripts.
// Require explicit completion so every guard/assertion failure also fails CI.
register_shutdown_function(function () use (&$completed): void {
    if (! $completed) {
        fwrite(STDERR, "Browser fixture/verification script did not complete.\n");
        exit(1);
    }
});

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (getenv('CI') !== 'true' || ! $app->environment('testing')
    || config('database.default') !== 'pgsql'
    || ! in_array(Illuminate\Support\Facades\DB::connection()->getConfig('host'), ['127.0.0.1', 'localhost'], true)) {
    throw new RuntimeException('Browser journey verification requires isolated CI testing PostgreSQL.');
}
$baseline = json_decode(file_get_contents(storage_path('app/browser-journeys.json')), true, flags: JSON_THROW_ON_ERROR);
$check = function (bool $condition, string $message): void {
    if (! $condition) {
        throw new RuntimeException($message);
    }
};
$compliance = App\Models\LandTransferApplication::findOrFail($baseline['applications']['compliance']);
$approval = App\Models\LandTransferApplication::findOrFail($baseline['applications']['approval']);
$check($compliance->status === 'pending_legal_review', 'Compliance journey did not resume the original stage.');
$notice = $compliance->complianceNotices()->sole();
$check($notice->category === 'other' && $notice->other_category === 'Survey clarification'
    && $notice->resolved_at !== null && $notice->resolution_note === 'Reviewed the corrected survey reference.',
    'Compliance notice was not durably recorded and resolved.');
$check($approval->status === 'approved' && $approval->release_status === 'released'
    && $approval->decision_officer_name === 'Journey PARPO II'
    && $approval->release_recipient_name === 'Journey Client'
    && $approval->release_logbook_reference === 'LOG-E2E-JOURNEY'
    && $approval->csm_status === 'received', 'Approval/release journey did not persist its evidence.');
$check($approval->clearance()->count() === 1, 'Final decision must generate exactly one clearance snapshot.');
$integrity = app(App\Services\ApplicationClearanceIntegrityService::class)->inspect($approval);
$check($integrity['valid'], 'Released clearance snapshot failed integrity verification.');

foreach ([$compliance->id => ['application_compliance_requested', 'application_compliance_resolved'],
    $approval->id => ['application_approved', 'application_ready_for_release', 'application_released_to_client']] as $id => $actions) {
    foreach ($actions as $action) {
        $check(App\Models\AuditLog::where('land_transfer_application_id', $id)->where('action', $action)->count() === 1,
            'Expected exactly one persisted audit event: '.$action);
    }
}
foreach (['landowner_compliance_required', 'landowner_compliance_resolved', 'landowner_final_decision',
    'landowner_ready_for_release', 'landowner_clearance_released'] as $type) {
    $check(App\Models\SystemNotification::where('user_id', $baseline['landowner_user_id'])->where('type', $type)->count() === 1,
        'Expected exactly one linked-landowner notification: '.$type);
}
$parcelIds = array_column($baseline['parcels'], 'id');
$holdings = App\Models\Landholding::whereIn('parcel_id', $parcelIds)->orderBy('id')->get()->toArray();
$check($holdings === $baseline['holdings'], 'Workflow or geometry journey mutated landholdings.');
$check(App\Models\Landholding::where('landowner_id', $baseline['transferee_id'])->count() === 0,
    'Approval must not execute an ownership transfer.');
foreach ($baseline['parcels'] as $original) {
    $current = App\Models\Parcel::findOrFail($original['id'])->toArray();
    if ((int) $original['id'] === (int) $baseline['geometry_parcel_id']) {
        $geometryFields = array_merge(['geometry_geojson', 'geometry_version', 'updated_at', 'record_revision'],
            App\Services\ParcelMapBounds::COLUMNS);
        foreach ($geometryFields as $field) {
            unset($current[$field], $original[$field]);
        }
    }
    $check($current === $original, 'Browser journey changed protected fields on Parcel #'.$original['id'].'.');
}
$parcel = App\Models\Parcel::findOrFail($baseline['geometry_parcel_id']);
$check((int) $parcel->geometry_version === 1, 'Invalid or stale geometry save changed the geometry version.');
$check(App\Models\ParcelGeometryRevision::where('parcel_id', $parcel->id)->where('source', 'geodetic_edit')->count() === 1,
    'Expected exactly one real geometry revision.');
$check(App\Models\AuditLog::where('auditable_type', App\Models\Parcel::class)->where('auditable_id', $parcel->id)
    ->where('action', 'geodetic_parcel_geometry_updated')->count() === 1, 'Expected exactly one geometry audit event.');
$points = $parcel->geometry_geojson['dar_source']['coordinates'] ?? [];
$check(count($points) >= 4 && (float) $points[0][0] === 500000.0 && (float) $points[1][0] === 500100.0,
    'The stale editor overwrote the first editor’s survey coordinates.');
echo "Browser journey persistence verified: compliance, approval/release, notifications, audit events, ownership scope, clearance integrity, geometry concurrency.\n";
$completed = true;
