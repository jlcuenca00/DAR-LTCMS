<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LandownerRegistrationReceived extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('DAR-LTCMS: Registration received')
            ->view('emails.landowner-registration-received', [
                'name' => $notifiable->name ?? null,
                'loginUrl' => route('login'),
                'logoUrl' => asset('images/favicon.png'),
            ]);
    }
}
