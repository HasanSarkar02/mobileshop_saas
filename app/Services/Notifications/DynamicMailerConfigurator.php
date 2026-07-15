<?php

namespace App\Services\Notifications;

use App\Models\Shop;
use RuntimeException;

/**
 * Builds a runtime Laravel mailer config for a shop's own SMTP credentials —
 * genuine white-label per-tenant email, mirroring how SMS is per-shop
 * provider/API key. Laravel has no first-class "per-request dynamic mailer"
 * API, so this uses the documented, supported pattern of registering a named
 * mailer's config at runtime before resolving it via Mail::mailer($name).
 * No extra package required.
 *
 * Only ever WRITES to a shop-unique key (mail.mailers.shop_{id}), never to a
 * shared key — safe even on long-running queue workers that reuse the same
 * PHP process across multiple shops' jobs, since each shop's config lives at
 * its own key and nothing is ever overwritten by another shop's job.
 * The "from" address/name is intentionally NOT put into shared config
 * (mail.from) for the same reason — it's set per-Mailable via ->from()
 * instead. See EmailChannelHandler / SendTestEmailAction.
 */
class DynamicMailerConfigurator
{
    public function configure(Shop $shop): string
    {
        if (! $shop->smtp_enabled || ! $shop->smtp_host) {
            throw new RuntimeException("SMTP is not configured for shop [{$shop->id}].");
        }

        $mailerName = 'shop_' . $shop->id;

        config(["mail.mailers.{$mailerName}" => [
            'transport' => 'smtp',
            'host' => $shop->smtp_host,
            'port' => $shop->smtp_port ?? 587,
            'encryption' => $shop->smtp_encryption ?: null,
            'username' => $shop->smtp_username,
            'password' => $shop->smtp_password,
            'timeout' => null,
        ]]);

        return $mailerName;
    }
}