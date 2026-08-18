<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\PasswordRecoveryCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordRecoveryEmailPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_recovery_notification_uses_branded_html_view(): void
    {
        $user = User::factory()->create([
            'name' => 'Sample User',
            'username' => 'sample_user',
            'email' => 'sample@example.com',
        ]);

        $mail = (new PasswordRecoveryCodeNotification('483921'))->toMail($user);

        $this->assertSame('DAR-LTCMS Password Recovery Code', $mail->subject);
        $this->assertSame('emails.password-recovery-code', $mail->view);
        $this->assertSame('483921', $mail->viewData['code']);
        $this->assertSame('Sample User', $mail->viewData['name']);
        $this->assertSame('sample_user', $mail->viewData['username']);

        $this->view('emails.password-recovery-code', $mail->viewData)
            ->assertSee('DAR-LTCMS')
            ->assertSee('483921')
            ->assertSee('Expires in 10 minutes')
            ->assertSee('Do not share it with anyone');
    }
}
