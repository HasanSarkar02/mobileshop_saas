<?php

namespace App\Notifications;

use App\Models\Shop;
use App\Services\Notifications\DynamicMailerConfigurator;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SetInitialPasswordNotification extends Notification
{
    use Queueable;

    // Make the Shop argument optional by setting it to null
    public function __construct(
        public readonly string $token,
        public readonly string $contextLabel,
        public readonly ?Shop $shop = null, // Set default to null
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = new MailMessage;
        
        // Check if shop exists before trying to configure dynamic mailer
        if ($this->shop) {
            $configurator = app(DynamicMailerConfigurator::class);
            $mailerName = $configurator->configure($this->shop);
            
            $message->mailer($mailerName)
                    ->from($this->shop->smtp_from_address ?: $this->shop->email, $this->shop->smtp_from_name ?: $this->shop->name);
        }

        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $line = ($this->contextLabel === 'reset') 
        ? 'You are receiving this email because we received a password reset request for your account.'
        : "An account has been created for you as {$this->contextLabel}.";

        return $message
            ->subject('Set up your account')
            ->line($line)
            ->action('Set Your Password', $url)
            ->line('This link will expire in 60 minutes.');
    }
}