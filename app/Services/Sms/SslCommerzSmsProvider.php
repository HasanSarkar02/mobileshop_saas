<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SslCommerzSmsProvider implements SmsProviderInterface
{
    public function __construct(private readonly string $apiKey) {}

    public function send(string $to, string $message, string $senderId): ?string
    {
        // Normalize
        $to = ltrim(preg_replace('/\D/', '', $to), '0');
        $to = '88' . (str_starts_with($to, '1') ? '0' . $to : $to);

        try {
            $response = Http::timeout(10)->get('https://sms.sslcommerz.com/smsapi', [
                'token'   => $this->apiKey,
                'to'      => $to,
                'msg'     => $message,
                'from'    => $senderId,
            ]);

            if ($response->successful()) {
                return 'sent';
            }

            Log::warning('SSLCommerz SMS send failed', ['status' => $response->status()]);
            return null;

        } catch (\Throwable $e) {
            Log::error('SSLCommerz SMS exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function name(): string { return 'SSLCommerz SMS'; }
}