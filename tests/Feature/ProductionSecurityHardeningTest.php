<?php

namespace Tests\Feature;

use App\Models\SourceRecordPackage;
use App\Models\User;
use App\Services\ProductionReadinessScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductionSecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_source_scan_is_delivered_only_through_staff_authorized_route(): void
    {
        Storage::fake('public');

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);
        $landowner = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'is_active' => true,
        ]);
        $geodetic = User::factory()->create([
            'role' => User::ROLE_GEODETIC,
            'is_active' => true,
        ]);

        $path = 'source-record-packages/private-reference.pdf';
        Storage::disk('public')->put($path, '%PDF-1.4 protected reference');

        SourceRecordPackage::create([
            'package_code' => 'SRC-SECURITY-001',
            'status' => SourceRecordPackage::STATUS_ENCODED,
            'source_record_scope' => 'reference_only',
            'encoded_by_user_id' => $staff->id,
            'source_book' => 'Security Test Source',
            'transcribed_by' => $staff->name,
            'transcription_date' => now()->toDateString(),
            'source_file_path' => $path,
            'source_file_original_filename' => 'private-reference.pdf',
            'source_file_mime_type' => 'application/pdf',
            'source_file_uploaded_by_user_id' => $staff->id,
            'source_file_uploaded_at' => now(),
        ]);

        $url = '/staff/protected-storage/'.$path;

        $staffResponse = $this->actingAs($staff)->get($url);
        $staffResponse->assertOk();
        $staffResponse->assertHeader('X-Content-Type-Options', 'nosniff');
        $staffResponse->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $this->assertStringContainsString('no-store', (string) $staffResponse->headers->get('Cache-Control'));

        $this->actingAs($landowner)->get($url)->assertForbidden();
        $this->actingAs($geodetic)->get($url)->assertForbidden();

        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));
    }

    public function test_protected_storage_rejects_unregistered_or_non_source_paths(): void
    {
        Storage::fake('public');

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
            'is_active' => true,
        ]);

        Storage::disk('public')->put('source-record-packages/unregistered.pdf', 'not registered');
        Storage::disk('public')->put('profile-photos/other-user.jpg', 'not a source scan');

        $this->actingAs($staff)
            ->get('/staff/protected-storage/source-record-packages/unregistered.pdf')
            ->assertNotFound();

        $this->actingAs($staff)
            ->get('/staff/protected-storage/profile-photos/other-user.jpg')
            ->assertNotFound();
    }

    public function test_storage_configuration_has_no_public_web_symlink_contract(): void
    {
        $this->assertSame([], config('filesystems.links'));
        $this->assertSame('local', config('filesystems.default'));
        $this->assertStringContainsString(
            '/staff/protected-storage',
            (string) config('filesystems.disks.public.url')
        );
    }

    public function test_production_readiness_scanner_passes_hardened_core_configuration(): void
    {
        File::ensureDirectoryExists(storage_path('app/private'));
        File::ensureDirectoryExists(storage_path('app/public'));

        config([
            'app.env' => 'production',
            'app.debug' => false,
            'app.url' => 'https://darltcms.me',
            'app.key' => 'base64:'.base64_encode(str_repeat('k', 32)),
            'session.driver' => 'database',
            'session.encrypt' => true,
            'session.secure' => true,
            'session.http_only' => true,
            'session.same_site' => 'lax',
            'filesystems.default' => 'local',
            'filesystems.disks.public.url' => 'https://darltcms.me/staff/protected-storage',
            'mail.default' => 'smtp',
            'logging.default' => 'single',
            'logging.channels.single.level' => 'warning',
        ]);

        $result = app(ProductionReadinessScanner::class)->scan();

        $this->assertTrue($result['read_only']);
        $this->assertTrue($result['ready']);
        $this->assertTrue($result['clean']);
        $this->assertSame(0, $result['blocking_count']);
        $this->assertSame(0, $result['warning_count']);
    }

    public function test_production_readiness_scanner_distinguishes_blockers_from_warnings(): void
    {
        File::ensureDirectoryExists(storage_path('app/private'));
        File::ensureDirectoryExists(storage_path('app/public'));

        config([
            'app.env' => 'production',
            'app.debug' => true,
            'app.url' => 'http://darltcms.me',
            'app.key' => 'base64:'.base64_encode(str_repeat('k', 32)),
            'session.driver' => 'database',
            'session.encrypt' => true,
            'session.secure' => false,
            'session.http_only' => true,
            'session.same_site' => 'lax',
            'filesystems.default' => 'local',
            'filesystems.disks.public.url' => 'http://darltcms.me/storage',
            'mail.default' => 'log',
            'logging.default' => 'single',
            'logging.channels.single.level' => 'debug',
        ]);

        $result = app(ProductionReadinessScanner::class)->scan();
        $issues = collect($result['issues'])->keyBy('code');

        $this->assertFalse($result['ready']);
        $this->assertSame('blocking', $issues['debug_enabled']['severity']);
        $this->assertSame('blocking', $issues['app_url_not_https']['severity']);
        $this->assertSame('blocking', $issues['session_cookie_not_secure']['severity']);
        $this->assertSame('blocking', $issues['source_storage_url_not_protected']['severity']);
        $this->assertSame('warning', $issues['mail_not_deliverable']['severity']);
        $this->assertSame('warning', $issues['debug_log_level_single']['severity']);
    }
}
