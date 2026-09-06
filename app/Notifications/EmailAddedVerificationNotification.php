<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class EmailAddedVerificationNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $email = Str::lower((string) ($notifiable->email ?? ''));

        $verificationUrl = URL::temporarySignedRoute(
            'email.added.verify',
            now()->addHours(24),
            [
                'user' => $notifiable->getKey(),
                'hash' => sha1($email),
            ]
        );

        return (new MailMessage)
            ->subject('Verify Your Email Address for DAR-LTCMS')
            ->view('emails.email-added-verification', [
                'name' => $notifiable->name ?? null,
                'username' => $notifiable->username ?? null,
                'email' => $notifiable->email ?? null,
                'verificationUrl' => $verificationUrl,
                'logoUrl' => asset('images/favicon.png'),
            ]);
    }
}
