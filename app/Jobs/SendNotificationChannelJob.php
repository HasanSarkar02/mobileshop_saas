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

    public int $tries = 4;

    public function __construct(public readonly int $deliveryId) {}

    /** Exponential backoff: 30s, 2min, 10min, 30min. */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

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
            $delivery->update([
                'status' => NotificationDeliveryStatus::Failed->value,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    public function failed(Throwable $e): void
    {
        NotificationDelivery::where('id', $this->deliveryId)->update([
            'status' => NotificationDeliveryStatus::Failed->value,
            'error_message' => 'All retry attempts exhausted: ' . $e->getMessage(),
        ]);
    }
}