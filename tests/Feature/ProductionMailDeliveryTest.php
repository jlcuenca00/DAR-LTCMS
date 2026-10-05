<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

class ProductionMailDeliveryTest extends TestCase
{
    use DatabaseMigrations;

    public function test_smtp_failure_does_not_advance_recovery_or_claim_code_was_sent(): void
    {
        $this->app['env'] = 'production';
        config(['mail.default' => 'smtp']);
        app('mail.manager')->mailer('smtp')->setSymfonyTransport(new class implements TransportInterface {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new TransportException('Simulated SMTP outage');
            }

            public function __toString(): string
            {
                return 'smtp';
            }
        });
        $user = User::factory()->create(['username' => 'mail_outage', 'email' => 'mail-outage@example.com']);
        $this->post(route('password.recovery.identify'), ['username' => $user->username]);
        $this->from(route('password.request'))->post(route('password.recovery.confirm-email'), ['email' => $user->email])
            ->assertSessionHasErrors('email')
            ->assertSessionHas('password_recovery.step', 'confirm_email')
            ->assertSessionMissing('password_recovery.otp_hash');
        $this->assertDatabaseHas('audit_logs', ['action' => 'password_recovery_code_delivery_failed', 'auditable_id' => $user->id]);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'password_recovery_code_sent', 'auditable_id' => $user->id]);
    }

    public function test_production_notification_guard_blocks_log_fallback_before_sending(): void
    {
        $this->app['env'] = 'production';
        config(['mail.default' => 'failover', 'mail.mailers.failover.mailers' => ['smtp', 'log']]);
        $user = User::factory()->create(['username' => 'unsafe_mail', 'email' => 'unsafe-mail@example.com']);
        $this->post(route('password.recovery.identify'), ['username' => $user->username]);
        $this->from(route('password.request'))->post(route('password.recovery.confirm-email'), ['email' => $user->email])
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('password_recovery.otp_hash');
        $this->assertDatabaseHas('audit_logs', ['action' => 'password_recovery_code_delivery_failed', 'auditable_id' => $user->id]);
    }

    public function test_profile_verification_runs_after_commit_and_failure_preserves_saved_profile(): void
    {
        $user = User::factory()->create(['password' => 'ProfilePassword123!', 'email' => 'before@example.com']);
        Notification::partialMock()->shouldReceive('send')->once()->andReturnUsing(function () use ($user): void {
            $this->assertSame(0, DB::transactionLevel());
            $this->assertSame('after@example.com', $user->fresh()->email);
            throw new TransportException('Simulated SMTP outage');
        });
        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated profile', 'email' => 'after@example.com', 'current_password' => 'ProfilePassword123!',
        ])->assertRedirect(route('profile.edit'))
            ->assertSessionHas('email_verification_status', fn ($message) => str_contains($message, 'could not be sent'));
        $this->assertSame('Updated profile', $user->fresh()->name);
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'profile_email_verification_email_failed', 'auditable_id' => $user->id]);
    }
}
