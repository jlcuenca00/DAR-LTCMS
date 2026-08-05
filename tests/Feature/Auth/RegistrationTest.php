<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();

        $this->post('/register', [
            'name' => 'Unauthorized User',
            'username' => 'unauthorized',
            'password' => 'Example-Password-123!',
            'password_confirmation' => 'Example-Password-123!',
        ])->assertNotFound();

        $this->assertDatabaseCount('users', 0);
    }
}
