<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionPaymentReviewed extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $status,
        private readonly string $invoiceNumber,
        private readonly ?string $rejectionReason = null
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $approved = $this->status === 'approved';
        $message = (new MailMessage())
            ->subject($approved ? 'Subscription payment approved' : 'Subscription payment needs correction')
            ->greeting('Hello '.$notifiable->name.',')
            ->line(
                $approved
                    ? 'Your full subscription payment has been approved and paid access is active.'
                    : 'Your subscription payment reference could not be approved.'
            )
            ->line('Invoice: '.$this->invoiceNumber);

        if (! $approved && $this->rejectionReason) {
            $message->line('Reason: '.$this->rejectionReason);
        }

        return $message
            ->action('View Subscription', route('subscription.status'))
            ->line(
                $approved
                    ? 'Thank you for your payment.'
                    : 'Review the details and submit a corrected payment reference.'
            );
    }
}
