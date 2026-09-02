<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $temporaryPassword
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your DAR-LTCMS Account Has Been Created')
            ->view('emails.account-created', [
                'name' => $notifiable->name ?? null,
                'username' => $notifiable->username ?? null,
                'temporaryPassword' => $this->temporaryPassword,
                'isActive' => (bool) ($notifiable->is_active ?? false),
                'loginUrl' => route('login'),
                'logoUrl' => asset('images/favicon.png'),
            ]);
    }
}
