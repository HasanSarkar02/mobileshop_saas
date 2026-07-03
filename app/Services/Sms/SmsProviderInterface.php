<?php

namespace App\Services\Sms;

interface SmsProviderInterface
{
    /**
     * Send a single SMS. Returns the provider's message ID or null on failure.
     */
    public function send(string $to, string $message, string $senderId): ?string;

    public function name(): string;
}