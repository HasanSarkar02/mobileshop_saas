<?php

namespace App\Reporting\DTOs;

use Carbon\Carbon;

final class DateRange
{
    public readonly Carbon $from;
    public readonly Carbon $to;

    public function __construct(Carbon $from, Carbon $to)
    {
        $this->from = $from->copy()->startOfDay();
        $this->to   = $to->copy()->endOfDay();
    }

    public static function today(): self
    {
        return new self(Carbon::today(), Carbon::today());
    }

    public static function thisMonth(): self
    {
        return new self(Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth());
    }

    public static function custom(string $from, string $to): self
    {
        return new self(Carbon::parse($from), Carbon::parse($to));
    }

    public function diffInDays(): int
    {
        return (int) $this->from->diffInDays($this->to) + 1;
    }

    /** Returns an equivalent previous period (same number of days, shifted back) */
    public function previousPeriod(): self
    {
        $days = $this->diffInDays();
        return new self(
            $this->from->copy()->subDays($days),
            $this->from->copy()->subDay(),
        );
    }

    public function toDisplayString(): string
    {
        if ($this->from->isSameDay($this->to)) {
            return $this->from->format('d M Y');
        }
        return $this->from->format('d M Y') . ' – ' . $this->to->format('d M Y');
    }

    public function cacheKey(): string
    {
        return $this->from->toDateString() . '_' . $this->to->toDateString();
    }
}