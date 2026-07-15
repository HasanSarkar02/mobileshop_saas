<?php

namespace App\Services\Notifications\Providers;

use App\Services\Notifications\Contracts\PushProviderInterface;

/** Default binding until Firebase/OneSignal/etc. is chosen. Always no-op. */
class NullPushProvider implements PushProviderInterface
{
    public function send(string $deviceToken, string $title, string $body, array $data): ?string
    {
        return null;
    }

    public function name(): string
    {
        return 'null';
    }
}