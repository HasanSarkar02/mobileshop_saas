<?php

namespace App\Services\Notifications\Channels;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Models\UserPushToken;
use App\Services\Notifications\Contracts\NotificationChannelHandler;
use App\Services\Notifications\Contracts\PushProviderInterface;

class PushChannelHandler implements NotificationChannelHandler
{
    public function __construct(private readonly PushProviderInterface $provider) {}

    public function send(NotificationDelivery $delivery): void
    {
        $recipient = $delivery->recipient()->with('notification')->first();
        $notification = $recipient?->notification;

        if (! $notification) {
            $delivery->update(['status' => NotificationDeliveryStatus::Failed->value, 'error_message' => 'Notification not found.']);
            return;
        }

        $tokens = UserPushToken::where('user_id', $recipient->user_id)->where('is_active', true)->get();

        if ($tokens->isEmpty()) {
            $delivery->update([
                'status' => NotificationDeliveryStatus::Skipped->value,
                'error_message' => 'No registered device tokens for this user.',
            ]);
            return;
        }

        $sentToAny = false;
        $lastMessageId = null;

        foreach ($tokens as $token) {
            $messageId = $this->provider->send(
                deviceToken: $token->token,
                title: $notification->title,
                body: $notification->body,
                data: [
                    'notification_id' => (string) $notification->id,
                    'reference_type' => (string) $notification->reference_type,
                    'reference_id' => (string) $notification->reference_id,
                    'deep_link' => (string) ($notification->payload['deep_link'] ?? ''),
                    'category' => $notification->category->value,
                    'priority' => $notification->priority->value,
                ],
            );

            if ($messageId !== null) {
                $sentToAny = true;
                $lastMessageId = $messageId;
                $token->update(['last_used_at' => now()]);
            }
        }

        $delivery->update([
            'status' => ($sentToAny ? NotificationDeliveryStatus::Sent : NotificationDeliveryStatus::Skipped)->value,
            'sent_at' => $sentToAny ? now() : null,
            'provider_reference' => $lastMessageId,
            'error_message' => $sentToAny ? null : 'No push provider configured (' . $this->provider->name() . ') — infrastructure-ready, awaiting Firebase/OneSignal wiring.',
        ]);
    }
}