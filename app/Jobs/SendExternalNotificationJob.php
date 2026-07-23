<?php

namespace App\Jobs;

use App\Enums\NotificationChannel;
use App\Models\Notification;
use App\Services\Notifications\Channels\SmsChannel;
use App\Services\Notifications\Channels\WhatsappChannel;
use App\Services\Notifications\ExternalRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendExternalNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $notificationId,
        public ExternalRecipient $recipient,
        public NotificationChannel $channel,
    ) {}

    public function handle(
        SmsChannel $sms,
        WhatsappChannel $whatsapp,
    ): void {
        $notification = Notification::find($this->notificationId);

        if (! $notification) {
            return;
        }

        match ($this->channel) {
            NotificationChannel::Sms =>
                $sms->sendExternal($notification, $this->recipient),

            NotificationChannel::Whatsapp =>
                $whatsapp->sendExternal($notification, $this->recipient),

            default => null,
        };
    }
}