<?php

namespace App\Services\Notifications;

use App\Enums\NotificationChannel;
use App\Enums\NotificationEventType;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\Shop;

class TemplateRenderer
{
    /** @return array{subject: ?string, body: string} */
    public function render(Shop $shop, NotificationEventType $eventType, NotificationChannel $channel, Notification $notification): array
    {
        $template = $this->resolveTemplate($shop, $eventType, $channel);
        $wrapped = $this->wrapPlaceholders($notification->payload['placeholders'] ?? []);

        if (! $template) {
            return [
                'subject' => $notification->title,
                'body' => $notification->body,
            ];
        }

        return [
            'subject' => $template->subject ? strtr($template->subject, $wrapped) : null,
            'body' => strtr($template->body, $wrapped),
        ];
    }

    /**
     * Explicit filter, NOT withoutGlobalScopes()-then-rely-on-ambient-context:
     * this may run inside a queued job with no TenantContext set, and
     * NotificationTemplate's own GlobalOrShopScope silently does nothing
     * when TenantContext is empty — which would leak every shop's templates.
     * $shop is always passed explicitly, so filter explicitly too.
     */
    private function resolveTemplate(Shop $shop, NotificationEventType $eventType, NotificationChannel $channel): ?NotificationTemplate
    {
        return NotificationTemplate::withoutGlobalScopes()
            ->where('event_type', $eventType->value)
            ->where('channel', $channel->value)
            ->where('is_active', true)
            ->where(fn ($q) => $q->where('shop_id', $shop->id)->orWhereNull('shop_id'))
            ->orderByRaw('shop_id IS NULL') // shop-specific override sorts before the system default
            ->first();
    }

    private function wrapPlaceholders(array $placeholders): array
    {
        $wrapped = [];
        foreach ($placeholders as $key => $value) {
            $wrapped['{{' . $key . '}}'] = (string) $value;
        }
        return $wrapped;
    }

    /** @return array<int, string> */
    public static function availablePlaceholders(): array
    {
        return ['customer_name', 'employee_name', 'supplier_name', 'invoice_no', 'sale_total', 'amount', 'branch', 'date'];
    }

    /** @return array<string, string> */
    public static function samplePlaceholders(): array
    {
        return [
            'customer_name' => 'Rahim Uddin',
            'employee_name' => 'Karim Hossain',
            'supplier_name' => 'ABC Distributors',
            'invoice_no' => 'INV-2026-00042',
            'sale_total' => '৳12,500.00',
            'amount' => '৳3,000.00',
            'branch' => 'Main Branch',
            'date' => now()->format('d M Y'),
        ];
    }
}