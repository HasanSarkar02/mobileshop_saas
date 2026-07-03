<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BulkSmsBdProvider implements SmsProviderInterface
{
    public function __construct(private readonly string $apiKey) {}

    public function send(string $to, string $message, string $senderId): ?string
    {
        // Normalize BD phone number
        $to = $this->normalizeNumber($to);

        try {
            $response = Http::timeout(10)->post('https://bulksmsbd.net/api/smsapi', [
                'api_key'   => $this->apiKey,
                'type'      => 'text',
                'number'    => $to,
                'senderid'  => $senderId,
                'message'   => $message,
            ]);

            $body = $response->json();

            if ($response->successful() && isset($body['response_code']) && $body['response_code'] == 202) {
                return $body['success_message'] ?? 'sent';
            }

            Log::warning('BulkSMSBD send failed', [
                'to'       => $to,
                'response' => $body,
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('BulkSMSBD exception', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function name(): string { return 'BulkSMSBD'; }

    private function normalizeNumber(string $number): string
    {
        $number = preg_replace('/\D/', '', $number);

        if (str_starts_with($number, '0')) {
            $number = '88' . $number;
        } elseif (str_starts_with($number, '1') && strlen($number) === 10) {
            $number = '880' . $number;
        }

        return $number;
    }
}