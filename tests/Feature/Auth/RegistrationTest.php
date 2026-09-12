<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_landowner_can_register_without_email_and_is_kept_pending(): void
    {
        $response = $this->post('/register', [
            'name' => 'Juan Dela Cruz',
            'username' => 'juan_landowner',
            'email' => '',
            'password' => 'Example-Password-123!',
            'password_confirmation' => 'Example-Password-123!',
            'privacy_consent' => '1',
        ]);

        $user = User::query()->where('username', 'juan_landowner')->first();

        $response->assertRedirect(route('landowner.registration.pending'));
        $this->assertNotNull($user);
        $this->assertSame(User::ROLE_LANDOWNER, $user->role);
        $this->assertSame(User::REGISTRATION_PENDING, $user->registration_status);
        $this->assertNull($user->email);
    }

    public function test_pending_landowner_cannot_open_landowner_records(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_LANDOWNER,
            'registration_status' => User::REGISTRATION_PENDING,
        ]);

        $this->actingAs($user)
            ->get('/landowner/dashboard')
            ->assertRedirect(route('landowner.registration.pending'));
    }
}
