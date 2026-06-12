<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $businessName,
        private readonly string $invoiceNumber,
        private readonly string $referenceNumber,
        private readonly int $amountCents
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Subscription payment requires review')
            ->greeting('Hello '.$notifiable->name.',')
            ->line($this->businessName.' submitted a full subscription payment reference.')
            ->line('Invoice: '.$this->invoiceNumber)
            ->line('Amount: Rs '.number_format($this->amountCents / 100, 2))
            ->line('Reference: '.$this->referenceNumber)
            ->action('Review Payment', route('platform.payment-submissions.index'));
    }
}
