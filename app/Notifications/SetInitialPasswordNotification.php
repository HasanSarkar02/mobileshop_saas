<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SetInitialPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $token,
        public readonly string $contextLabel,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $line = $this->contextLabel === 'reset'
            ? 'You are receiving this email because we received a password reset request for your account.'
            : "An account has been created for you as {$this->contextLabel}.";

        return (new MailMessage)
            ->from(
                config('mail.from.address'),
                config('mail.from.name')
            )
            ->subject('Set up your ShopERP account')
            ->line($line)
            ->action('Set Your Password', $url)
            ->line('This link will expire in 60 minutes.');
    }
}