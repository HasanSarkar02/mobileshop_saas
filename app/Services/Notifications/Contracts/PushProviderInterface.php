<?php

namespace App\Services\Notifications\Contracts;

interface PushProviderInterface
{
    /** Mirrors SmsProviderInterface::send()'s contract: message id or null. */
    public function send(string $deviceToken, string $title, string $body, array $data): ?string;

    public function name(): string;
}