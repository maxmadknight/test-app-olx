<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Advertisement;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdvertisementPriceChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Advertisement $advertisement,
        public int $oldPrice,
        public string $oldCurrency
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(Subscription $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Price change on OLX ad')
            ->greeting('Hello!')
            ->line('Price for advertisement was changed')
            ->line('Advertisement: '.$this->advertisement->url)
            ->line('Old price: '.$this->oldPrice.' '.$this->oldCurrency)
            ->line('New price: '.$this->advertisement->price.' '.$this->advertisement->currency)
            ->action('View advertisement', $this->advertisement->url)
            ->line('Thank you for using our service!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'advertisement_id' => $this->advertisement->id,
            'old_price'        => $this->oldPrice,
            'old_currency'     => $this->oldCurrency,
            'new_price'        => $this->advertisement->price,
            'new_currency'     => $this->advertisement->currency,
            'url'              => $this->advertisement->url,
        ];
    }
}
