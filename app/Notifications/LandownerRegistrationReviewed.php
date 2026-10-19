<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LandownerRegistrationReviewed extends Notification
{
    public function toMail(object $notifiable): MailMessage
    {
        $approved = $notifiable->registration_status === User::REGISTRATION_APPROVED;

        return (new MailMessage)
            ->subject('DAR-LTCMS: Registration review update')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($approved ? 'Your landowner registration has been approved.'
                : 'Your registration needs further attention. Sign in to check your registration status.')
            ->line($approved ? 'Sign in to access the records linked to your account. Account approval does not establish land ownership or clearance approval.'
                : 'Your land records remain unavailable while your registration is unresolved.')
            ->action('Sign in to DAR-LTCMS', route('login'));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}
