<?php

namespace App\Services\Notifications\Contracts;

interface WhatsAppProviderInterface
{
    public function send(string $phoneNumber, string $message, array $data = []): ?string;

    public function name(): string;
}