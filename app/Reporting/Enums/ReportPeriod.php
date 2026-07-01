<?php

namespace App\Reporting\Enums;

use App\Reporting\DTOs\DateRange;
use Carbon\Carbon;

enum ReportPeriod: string
{
    case Today         = 'today';
    case Yesterday     = 'yesterday';
    case Last7Days     = 'last_7_days';
    case Last30Days    = 'last_30_days';
    case ThisWeek      = 'this_week';
    case LastWeek      = 'last_week';
    case ThisMonth     = 'this_month';
    case LastMonth     = 'last_month';
    case ThisQuarter   = 'this_quarter';
    case LastQuarter   = 'last_quarter';
    case ThisYear      = 'this_year';
    case LastYear      = 'last_year';
    case Custom        = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Today       => 'Today',
            self::Yesterday   => 'Yesterday',
            self::Last7Days   => 'Last 7 Days',
            self::Last30Days  => 'Last 30 Days',
            self::ThisWeek    => 'This Week',
            self::LastWeek    => 'Last Week',
            self::ThisMonth   => 'This Month',
            self::LastMonth   => 'Last Month',
            self::ThisQuarter => 'This Quarter',
            self::LastQuarter => 'Last Quarter',
            self::ThisYear    => 'This Year',
            self::LastYear    => 'Last Year',
            self::Custom      => 'Custom Range',
        };
    }

    public function toDateRange(): DateRange
    {
        $now = Carbon::now();

        return match ($this) {
            self::Today       => new DateRange($now->copy()->startOfDay(), $now->copy()->endOfDay()),
            self::Yesterday   => new DateRange($now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()),
            self::Last7Days   => new DateRange($now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()),
            self::Last30Days  => new DateRange($now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()),
            self::ThisWeek    => new DateRange($now->copy()->startOfWeek(), $now->copy()->endOfWeek()),
            self::LastWeek    => new DateRange($now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek()),
            self::ThisMonth   => new DateRange($now->copy()->startOfMonth(), $now->copy()->endOfMonth()),
            self::LastMonth   => new DateRange($now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()),
            self::ThisQuarter => new DateRange($now->copy()->startOfQuarter(), $now->copy()->endOfQuarter()),
            self::LastQuarter => new DateRange($now->copy()->subQuarter()->startOfQuarter(), $now->copy()->subQuarter()->endOfQuarter()),
            self::ThisYear    => new DateRange($now->copy()->startOfYear(), $now->copy()->endOfYear()),
            self::LastYear    => new DateRange($now->copy()->subYear()->startOfYear(), $now->copy()->subYear()->endOfYear()),
            self::Custom      => new DateRange($now->copy()->startOfDay(), $now->copy()->endOfDay()),
        };
    }

    /** Comparison period for percentage change calculations */
    public function previousPeriod(): self
    {
        return match ($this) {
            self::Today       => self::Yesterday,
            self::Yesterday   => self::Custom, // day before yesterday — handle in service
            self::ThisWeek    => self::LastWeek,
            self::ThisMonth   => self::LastMonth,
            self::ThisQuarter => self::LastQuarter,
            self::ThisYear    => self::LastYear,
            default           => self::Custom,
        };
    }
}