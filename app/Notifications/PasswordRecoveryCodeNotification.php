<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordRecoveryCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $code
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('DAR-LTCMS Password Recovery Code')
            ->view('emails.password-recovery-code', [
                'code' => $this->code,
                'name' => $notifiable->name ?? null,
                'username' => $notifiable->username ?? null,
                'logoUrl' => asset('images/favicon.png'),
            ]);
    }
}
