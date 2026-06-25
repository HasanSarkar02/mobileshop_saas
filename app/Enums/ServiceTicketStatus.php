<?php
namespace App\Enums;

enum ServiceTicketStatus: string
{
    case Received   = 'received';
    case Diagnosing = 'diagnosing';
    case InRepair   = 'in_repair';
    case Ready      = 'ready';
    case Delivered  = 'delivered';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Received   => 'Received',
            self::Diagnosing => 'Diagnosing',
            self::InRepair   => 'In Repair',
            self::Ready      => 'Ready for Pickup',
            self::Delivered  => 'Delivered',
            self::Cancelled  => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Received   => 'badge-gray',
            self::Diagnosing => 'badge-yellow',
            self::InRepair   => 'badge-blue',
            self::Ready      => 'badge-green',
            self::Delivered  => 'badge-green',
            self::Cancelled  => 'badge-red',
        };
    }

    public function nextStatuses(): array
    {
        return match ($this) {
            self::Received   => [self::Diagnosing, self::Cancelled],
            self::Diagnosing => [self::InRepair, self::Cancelled],
            self::InRepair   => [self::Ready, self::Cancelled],
            self::Ready      => [self::Delivered],
            self::Delivered  => [],
            self::Cancelled  => [],
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled]);
    }
}