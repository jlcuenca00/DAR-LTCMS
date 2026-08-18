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
            ->greeting('DAR-LTCMS Password Recovery')
            ->line('A password recovery request was made for your DAR-LTCMS account.')
            ->line("Your 6-digit verification code is: {$this->code}")
            ->line('This code expires in 10 minutes and can only be used once.')
            ->line('If you did not request a password reset, you may ignore this email and keep your current password.');
    }
}
