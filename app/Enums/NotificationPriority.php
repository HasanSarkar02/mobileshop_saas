<?php

namespace App\Enums;

enum NotificationPriority: string
{
    case Critical = 'critical';
    case Urgent = 'urgent';
    case High = 'high';
    case Normal = 'normal';
    case Low = 'low';

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critical',
            self::Urgent => 'Urgent',
            self::High => 'High',
            self::Normal => 'Normal',
            self::Low => 'Low',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Critical => 'badge-red',
            self::Urgent => 'badge-yellow',
            self::High => 'badge-indigo',
            self::Normal => 'badge-blue',
            self::Low => 'badge-gray',
        };
    }

    /** Higher = more important. Sorts the bell dropdown and Notification Center. */
    public function weight(): int
    {
        return match ($this) {
            self::Critical => 5,
            self::Urgent => 4,
            self::High => 3,
            self::Normal => 2,
            self::Low => 1,
        };
    }

    /**
     * Minutes an action-required notification can sit un-acted-on before
     * EscalatePendingNotifications bumps it. Null = never auto-escalates.
     */
    public function escalationWindowMinutes(): ?int
    {
        return match ($this) {
            self::Critical => 15,
            self::Urgent => 60,
            self::High => 240,
            self::Normal => 1440,
            self::Low => null,
        };
    }

    public function nextLevel(): self
    {
        return match ($this) {
            self::Low => self::Normal,
            self::Normal => self::High,
            self::High => self::Urgent,
            self::Urgent, self::Critical => self::Critical,
        };
    }
}