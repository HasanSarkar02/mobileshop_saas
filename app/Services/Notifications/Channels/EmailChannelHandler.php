<?php

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Mail\NotificationMail;
use App\Models\NotificationDelivery;
use App\Models\Shop;
use App\Services\Notifications\Contracts\NotificationChannelHandler;
use App\Services\Notifications\DynamicMailerConfigurator;
use App\Services\Notifications\TemplateRenderer;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EmailChannelHandler implements NotificationChannelHandler
{
    public function __construct(
        private readonly TemplateRenderer $templates,
        private readonly DynamicMailerConfigurator $mailerConfig,
    ) {}

    public function send(NotificationDelivery $delivery): void
    {
        $recipient = $delivery->recipient()->with(['notification', 'user'])->first();
        $notification = $recipient?->notification;
        $user = $recipient?->user;

        if (! $notification || ! $user || ! $user->email) {
            $delivery->update([
                'status' => NotificationDeliveryStatus::Skipped->value,
                'error_message' => 'Recipient has no email address on file.',
            ]);
            return;
        }

        $shop = Shop::withoutGlobalScopes()->find($notification->shop_id);

        if (! $shop) {
            $delivery->update(['status' => NotificationDeliveryStatus::Failed->value, 'error_message' => 'Shop not found.']);
            return;
        }

        if (! $shop->smtp_enabled || ! $shop->smtp_host) {
            $delivery->update([
                'status' => NotificationDeliveryStatus::Skipped->value,
                'error_message' => 'SMTP is not configured for this shop.',
            ]);
            return;
        }

        $rendered = $this->templates->render($shop, $notification->event_type, NotificationChannel::Email, $notification);

        try {
            $mailerName = $this->mailerConfig->configure($shop);

            Mail::mailer($mailerName)
                ->to($user->email)
                ->send((new NotificationMail(
                    mailSubject: $rendered['subject'] ?: $notification->title,
                    bodyText: $rendered['body'],
                    actionUrl: $notification->payload['deep_link'] ?? null,
                    actionLabel: $notification->action_label,
                    shopName: $shop->name,
                ))->from(
                    $shop->smtp_from_address ?: $shop->email,
                    $shop->smtp_from_name ?: $shop->name,
                ));

            $delivery->update([
                'status' => NotificationDeliveryStatus::Sent->value,
                'sent_at' => now(),
            ]);
        } catch (Throwable $e) {
            $delivery->update([
                'status' => NotificationDeliveryStatus::Failed->value,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}