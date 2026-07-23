<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BulkSmsBdProvider implements SmsProviderInterface
{
    private const ENDPOINT = 'https://api.sms.net.bd/sendsms';

    public function __construct(
        private readonly string $apiKey
    ) {}

    public function send(string $to, string $message, string $senderId = ''): ?string
    {
        $to = $this->normalizeNumber($to);

        try {
            $response = Http::timeout(15)
                ->asForm()
                ->post(self::ENDPOINT, [
                    'api_key'   => $this->apiKey,
                    'to'        => $to,
                    'msg'       => $message,
                ]);

            if (! $response->successful()) {
                Log::warning('BulkSMSBD HTTP Error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return null;
            }

            $body = $response->json();

            if (($body['error'] ?? 1) === 0) {
                return (string) ($body['data']['request_id'] ?? 'sent');
            }

            Log::warning('BulkSMSBD API Error', [
                'response' => $body,
            ]);

            return null;

        } catch (\Throwable $e) {
            Log::error('BulkSMSBD Exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function name(): string
    {
        return 'BulkSMSBD';
    }

    private function normalizeNumber(string $number): string
    {
        $number = preg_replace('/\D/', '', $number);

        // 017xxxxxxxx
        if (str_starts_with($number, '0')) {
            return '88' . $number;
        }

        // 17xxxxxxxx
        if (strlen($number) === 10 && str_starts_with($number, '1')) {
            return '880' . $number;
        }

        // already 88017xxxxxxxx
        if (str_starts_with($number, '880')) {
            return $number;
        }

        return $number;
    }
}