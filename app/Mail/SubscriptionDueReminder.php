<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionDueReminder extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly object $subscription) {}

    public function build(): self
    {
        return $this->subject("Payment Reminder — ShopERP Subscription")
            ->html("
                <h2>Payment Reminder</h2>
                <p>Dear {$this->subscription->name},</p>
                <p>Your ShopERP subscription payment of
                   <strong>৳" . number_format($this->subscription->price_at_signup, 2) . "</strong>
                   is due on <strong>{$this->subscription->next_billing_date}</strong>.</p>
                <p>Please make the payment to continue uninterrupted access.</p>
                <p>Contact us if you have any questions.</p>
                <p>ShopERP Team</p>
            ");
    }
}