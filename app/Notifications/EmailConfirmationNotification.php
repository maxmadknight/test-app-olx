<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct() {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail(Subscription $notifiable): MailMessage
    {
        $verificationLink = route('verify-email', [
            'token' => $notifiable->verification_token,
            'email' => $notifiable->email,
        ]);

        $expirationTime = $notifiable->token_expires_at
            ? $notifiable->token_expires_at->diffForHumans()
            : '24 hours';

        return (new MailMessage)
            ->subject('Please confirm your email address')
            ->greeting('Hello!')
            ->line('Thank you for using our application.')
            ->line('To complete your subscription, please click the button below.')
            ->action('Confirm Email Address', $verificationLink)
            ->line("This verification link will expire {$expirationTime}.")
            ->line('If you did not create an account, no further action is required.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'email'      => $notifiable->email,
            'token'      => $notifiable->verification_token,
            'expires_at' => $notifiable->token_expires_at,
        ];
    }
}
