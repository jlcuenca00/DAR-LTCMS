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
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your landowner registration through Google was received.')
            ->line('Your account is waiting for DAR staff review. This email is not an approval of your account, land ownership, or clearance.')
            ->line('Land records, parcel maps, and clearance outputs remain locked until staff verifies your identity and links the correct landowner record.')
            ->action('Sign in to DAR-LTCMS', route('login'))
            ->line('Use Continue with Google on the sign-in page. You do not need to register again.')
            ->line('For assistance, contact the DAR Negros Oriental Provincial Office.');
    }
}
