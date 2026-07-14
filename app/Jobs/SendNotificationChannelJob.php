<?php

namespace App\Jobs;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Services\Notifications\NotificationChannelManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendNotificationChannelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public readonly int $deliveryId) {}

    public function handle(NotificationChannelManager $channels): void
    {
        $delivery = NotificationDelivery::find($this->deliveryId);

        if (! $delivery) {
            return;
        }

        $delivery->increment('attempts');

        try {
            $channels->handlerFor($delivery->channel)->send($delivery->fresh());
        } catch (Throwable $e) {
            // A channel handler must never throw (see the contract), but this
            // is the last line of defense — a notification failure must never
            // surface to the user or interrupt the queue worker.
            $delivery->update([
                'status' => NotificationDeliveryStatus::Failed->value,
                'error_message' => $e->getMessage(),
            ]);
        }
    }
}