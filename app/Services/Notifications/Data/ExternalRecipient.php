<?php

namespace App\Services\Notifications\Data;

class ExternalRecipient
{
    public function __construct(
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly string $name,
    ) {}
}