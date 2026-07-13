<?php

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Models\Shop;
use App\Services\Notifications\Contracts\NotificationChannelHandler;
use App\Services\SmsService;

/**
 * Delegates to the EXISTING, UNMODIFIED SmsService::send(). This class owns
 * ZERO SMS transport logic — no provider resolution, no phone normalization,
 * no shop-level sms_enabled gating. It only renders the notification into an
 * SMS-appropriate body and calls the same method SaleConfirmationAction
 * already calls today for sale receipts.
 *
 * SmsLog remains the transport-level audit trail (exact provider response).
 * This channel's own NotificationDelivery row is the dispatch-decision-level
 * record ("the Notification Engine asked SMS to send this, and it reported
 * X"). These are two different layers of the same audit trail, not
 * duplicated logic — the same relationship TreasuryTransaction (decision
 * document) already has with JournalEntry (ledger truth).
 */
class SmsChannelHandler implements NotificationChannelHandler
{
    public function __construct(private readonly SmsService $sms) {}

    public function send(NotificationDelivery $delivery): void
    {
        $recipient = $delivery->recipient()->with(['notification', 'user'])->first();
        $notification = $recipient?->notification;
        $user = $recipient?->user;

        if (! $notification || ! $user || ! $user->phone) {
            $delivery->update([
                'status' => NotificationDeliveryStatus::Skipped->value,
                'error_message' => 'Recipient has no phone number on file.',
            ]);
            return;
        }

        /** @var Shop|null $shop */
        $shop = Shop::withoutGlobalScopes()->find($notification->shop_id);

        if (! $shop) {
            $delivery->update([
                'status' => NotificationDeliveryStatus::Failed->value,
                'error_message' => 'Shop not found.',
            ]);
            return;
        }

        $body = $notification->title . ': ' . $notification->body;

        $sent = $this->sms->send(
            shop: $shop,
            to: $user->phone,
            message: $body,
            template: 'notification_' . $notification->event_type->value,
            reference: $notification,
            createdBy: $notification->created_by,
        );

        $delivery->update([
            'status' => ($sent ? NotificationDeliveryStatus::Sent : NotificationDeliveryStatus::Failed)->value,
            'sent_at' => $sent ? now() : null,
            'error_message' => $sent
                ? null
                : 'SmsService::send() returned false — see sms_logs for the provider response.',
        ]);
    }
}