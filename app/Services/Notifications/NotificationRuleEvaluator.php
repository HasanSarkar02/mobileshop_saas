<?php

namespace App\Services\Notifications;

use App\Enums\NotificationEventType;
use App\Models\NotificationRule;
use App\Models\Shop;

class NotificationRuleEvaluator
{
    private const OPERATORS = ['>', '>=', '<', '<=', '==', '!='];

    public function resolve(Shop $shop, NotificationEventType $eventType, array $context): ?NotificationRule
    {
        $rules = NotificationRule::withoutGlobalScopes()
            ->where('shop_id', $shop->id)
            ->where('event_type', $eventType->value)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($rules as $rule) {
            if ($this->matches($rule->conditions ?? [], $context)) {
                return $rule;
            }
        }

        return null;
    }

    private function matches(array $conditions, array $context): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? null;
            $value = $condition['value'] ?? null;

            if (! $field || ! in_array($operator, self::OPERATORS, true) || ! array_key_exists($field, $context)) {
                return false;
            }

            $actual = $context[$field];

            $result = match ($operator) {
                '>' => $actual > $value,
                '>=' => $actual >= $value,
                '<' => $actual < $value,
                '<=' => $actual <= $value,
                '==' => $actual == $value,
                '!=' => $actual != $value,
                default => false,
            };

            if (! $result) {
                return false;
            }
        }

        return true;
    }
}