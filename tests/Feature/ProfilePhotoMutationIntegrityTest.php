<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProfilePhotoMutationIntegrityTest extends TestCase
{
    use DatabaseMigrations;

    private function userWithPhoto(): User
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create(['profile_photo_path' => 'profile-photos/original.png']);
        Storage::disk('local')->put($user->profile_photo_path, 'original');
        Storage::disk('public')->put($user->profile_photo_path, 'legacy duplicate');
        return $user;
    }

    public function test_successful_photo_replacement_deletes_old_files_after_commit(): void
    {
        $user = $this->userWithPhoto();
        $old = $user->profile_photo_path;
        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name, 'email' => $user->email,
            'profile_photo' => UploadedFile::fake()->image('replacement.png'),
        ])->assertSessionHasNoErrors();
        $new = $user->fresh()->profile_photo_path;
        $this->assertNotSame($old, $new);
        Storage::disk('local')->assertExists($new);
        Storage::disk('local')->assertMissing($old);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_photo_removal_waits_for_the_outer_transaction_to_commit(): void
    {
        $user = $this->userWithPhoto();
        $old = $user->profile_photo_path;
        DB::beginTransaction();
        try {
            $this->actingAs($user)->patch(route('profile.update'), [
                'name' => $user->name, 'email' => $user->email, 'remove_profile_photo' => '1',
            ])->assertSessionHasNoErrors();
            $this->assertNull($user->fresh()->profile_photo_path);
            Storage::disk('local')->assertExists($old);
            DB::commit();
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
        Storage::disk('local')->assertMissing($old);
        Storage::disk('public')->assertMissing($old);
    }

    public function test_failed_photo_replacement_preserves_original_and_cleans_up_the_new_upload(): void
    {
        $this->assertFailedMutationPreservesFiles(false);
    }

    public function test_failed_photo_removal_preserves_original_files(): void
    {
        $this->assertFailedMutationPreservesFiles(true);
    }

    private function assertFailedMutationPreservesFiles(bool $remove): void
    {
        $user = $this->userWithPhoto();
        $old = $user->profile_photo_path;
        $fail = true;
        Event::listen('eloquent.creating: '.AuditLog::class, function (AuditLog $log) use (&$fail) {
            if ($fail && $log->action === 'profile_updated') {
                throw new RuntimeException('Simulated audit persistence failure');
            }
        });
        $payload = ['name' => 'Must Roll Back', 'email' => $user->email];
        if ($remove) {
            $payload['remove_profile_photo'] = '1';
        } else {
            $payload['profile_photo'] = UploadedFile::fake()->image('replacement.png');
        }
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($user)->patch(route('profile.update'), $payload);
            $this->fail('Expected persistence failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated audit persistence failure', $exception->getMessage());
        } finally {
            $fail = false;
        }
        $this->assertSame($old, $user->fresh()->profile_photo_path);
        $this->assertNotSame('Must Roll Back', $user->fresh()->name);
        Storage::disk('local')->assertExists($old);
        Storage::disk('public')->assertExists($old);
        $this->assertSame([$old], Storage::disk('local')->allFiles('profile-photos'));
    }
}
