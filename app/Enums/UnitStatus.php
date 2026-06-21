<?php

namespace App\Enums;

enum UnitStatus: string
{
    case InStock = 'in_stock';
    case Reserved = 'reserved';
    case Sold = 'sold';
    case ReturnedPendingInspection = 'returned_pending_inspection';
    case Damaged = 'damaged';
    case Lost = 'lost';
    case RmaToSupplier = 'rma_to_supplier';
    case WrittenOff = 'written_off';

    /**
     * The real lifecycle graph from the architecture review — not a linear
     * chain. "Exchanged" never appears here because it isn't a unit state;
     * it's a Return plus a separate Sale on a different unit.
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::InStock => [self::Reserved, self::Sold, self::Lost],
            self::Reserved => [self::InStock, self::Sold],
            self::Sold => [self::ReturnedPendingInspection],
            self::ReturnedPendingInspection => [self::InStock, self::Damaged],
            self::Damaged => [self::WrittenOff, self::RmaToSupplier],
            self::Lost => [self::WrittenOff],
            self::RmaToSupplier => [self::WrittenOff],
            self::WrittenOff => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /** Statuses where the physical unit has definitively left this shop's active pool — these free up the IMEI for a future legitimate trade-in re-entry. */
    public function isArchivable(): bool
    {
        return in_array($this, [self::Sold, self::WrittenOff, self::RmaToSupplier], true);
    }
}