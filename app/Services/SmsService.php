<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\SmsLog;
use App\Services\Sms\BulkSmsBdProvider;
use App\Services\Sms\SmsProviderInterface;
use App\Services\Sms\SslCommerzSmsProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SmsService
{
    // ── Core Send ──────────────────────────────────────────────────────────────

    public function send(
        Shop    $shop,
        string  $to,
        string  $message,
        string  $template,
        ?Model  $reference = null,
        ?int    $createdBy = null,
    ): bool {
        if (! $shop->sms_enabled || ! $shop->sms_api_key) {
            return false;
        }

        $provider  = $this->resolveProvider($shop);
        $messageId = null;
        $status    = 'failed';

        try {
            $messageId = $provider->send($to, $message, $shop->sms_sender_id ?? $shop->name);
            $status    = $messageId ? 'sent' : 'failed';
        } catch (\Throwable $e) {
            Log::error('SMS send error', ['shop_id' => $shop->id, 'error' => $e->getMessage()]);
        }

        SmsLog::create([
            'shop_id'            => $shop->id,
            'to_number'          => $to,
            'template'           => $template,
            'message'            => $message,
            'status'             => $status,
            'message_id'         => $messageId,
            'provider_response'  => $messageId ?? 'failed',
            'reference_type'     => $reference?->getMorphClass(),
            'reference_id'       => $reference?->getKey(),
            'created_by'         => $createdBy ?? Auth::id(),
        ]);

        return $status === 'sent';
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function canSend(Shop $shop, string $feature): bool
    {
        return $shop->sms_enabled && $shop->sms_api_key && $shop->$feature;
    }

    private function resolveProvider(Shop $shop): SmsProviderInterface
    {
        return match ($shop->sms_provider) {
            'ssl_commerz' => new SslCommerzSmsProvider($shop->sms_api_key),
            default       => new BulkSmsBdProvider($shop->sms_api_key),
        };
    }
}