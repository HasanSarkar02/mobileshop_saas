<?php

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationChannel;
use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Models\Shop;
use App\Services\Notifications\Contracts\NotificationChannelHandler;
use App\Services\Notifications\TemplateRenderer;
use App\Services\SmsService;

class SmsChannelHandler implements NotificationChannelHandler
{
    public function __construct(
        private readonly SmsService $sms,
        private readonly TemplateRenderer $templates,
    ) {}

    public function send(NotificationDelivery $delivery): void
    {
        $recipient = $delivery->recipient()->with(['notification', 'user'])->first();
        $notification = $recipient?->notification;
        $phone = $recipient?->isExternal() ? $recipient->external_phone : $recipient?->user?->phone;

        if (! $notification || ! $recipient || ! $phone) {
            $delivery->update([
                'status' => NotificationDeliveryStatus::Skipped->value,
                'error_message' => 'Recipient has no phone number on file.',
            ]);
            return;
        }

        $shop = Shop::withoutGlobalScopes()->find($notification->shop_id);

        if (! $shop) {
            $delivery->update(['status' => NotificationDeliveryStatus::Failed->value, 'error_message' => 'Shop not found.']);
            return;
        }

        if (! $shop->sms_enabled || ! $shop->sms_api_key) {
            $delivery->update([
                'status' => NotificationDeliveryStatus::Skipped->value,
                'error_message' => 'SMS is not configured for this shop.',
            ]);
            return;
        }

        $rendered = $this->templates->render($shop, $notification->event_type, NotificationChannel::Sms, $notification);

        $sent = $this->sms->send(
            shop: $shop,
            to: $phone,
            message: $rendered['body'],
            template: 'notification_' . $notification->event_type->value,
            reference: $notification,
            createdBy: $notification->created_by,
        );

        $delivery->update([
            'status' => ($sent ? NotificationDeliveryStatus::Sent : NotificationDeliveryStatus::Failed)->value,
            'sent_at' => $sent ? now() : null,
            'error_message' => $sent ? null : 'SmsService::send() returned false — see sms_logs for the provider response.',
        ]);
    }
}