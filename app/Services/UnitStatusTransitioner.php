<?php

namespace App\Services;

use App\Enums\UnitStatus;
use App\Models\ProductUnit;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class UnitStatusTransitioner
{
    public function transition(ProductUnit $unit, UnitStatus $to, ?Model $disposition = null): ProductUnit
    {
        if (! $unit->status->canTransitionTo($to)) {
            throw new RuntimeException("Cannot move unit [{$unit->serial_number}] from {$unit->status->value} to {$to->value}.");
        }

        $unit->status = $to;
        $unit->is_archived = $to->isArchivable();

        if ($disposition) {
            $unit->disposition_type = $disposition->getMorphClass();
            $unit->disposition_id = $disposition->getKey();
        }

        if ($to === UnitStatus::Sold) {
            $unit->sold_at = now();
        }

        $unit->save();

        return $unit;
    }

    public function reverseVoidedSale(int $productUnitId, Sale $sale): ProductUnit
    {
        /** @var ProductUnit $unit */
        $unit = ProductUnit::withoutGlobalScopes()
            ->where('id', $productUnitId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($unit->status !== UnitStatus::Sold) {
            throw new RuntimeException(
                "Cannot void sale [{$sale->sale_number}]: unit [{$unit->serial_number}] is no longer in Sold status "
                . "(currently: {$unit->status->value}). It may have already entered a return, warranty, or RMA "
                . "process independently. Resolve that process first, then reconcile this unit manually."
            );
        }

        if ($unit->disposition_type !== Sale::class || (int) $unit->disposition_id !== (int) $sale->id) {
            throw new RuntimeException(
                "Cannot void sale [{$sale->sale_number}]: unit [{$unit->serial_number}] is marked Sold under a "
                . "different disposition record than this sale. Its Sold status did not originate from this sale "
                . "and must not be reversed by voiding it."
            );
        }

        $unit->status = UnitStatus::InStock;
        $unit->is_archived = false;
        $unit->sold_at = null;
        $unit->disposition_type = null;
        $unit->disposition_id = null;
        $unit->save();

        return $unit;
    }

    public function markDamaged(int $productUnitId): ProductUnit
    {
        $unit = ProductUnit::withoutGlobalScopes()
            ->where('id', $productUnitId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($unit->status !== UnitStatus::InStock) {
            throw new RuntimeException(
                "Cannot mark unit [{$unit->serial_number}] as damaged: current status is "
                . "{$unit->status->value}, expected in_stock. It may have just been modified by another request."
            );
        }

        $unit->status = UnitStatus::Damaged;
        $unit->is_archived = false;
        $unit->save();

        return $unit;
    }

    public function writeOff(int $productUnitId): array
    {
        $allowed = [UnitStatus::InStock, UnitStatus::Damaged, UnitStatus::Lost];

        $unit = ProductUnit::withoutGlobalScopes()
            ->where('id', $productUnitId)
            ->lockForUpdate()
            ->firstOrFail();

        if (! in_array($unit->status, $allowed, true)) {
            throw new RuntimeException(
                "Cannot write off unit [{$unit->serial_number}]: current status is {$unit->status->value}."
            );
        }

        $wasAlreadyDamaged = $unit->status === UnitStatus::Damaged;

        $unit->status = UnitStatus::WrittenOff;
        $unit->is_archived = true;
        $unit->save();

        return ['unit' => $unit, 'was_already_damaged' => $wasAlreadyDamaged];
    }

    public function reserveSerialized(int $productUnitId): ProductUnit
    {
        $unit = ProductUnit::withoutGlobalScopes()
            ->where('id', $productUnitId)
            ->lockForUpdate()
            ->firstOrFail();

        if (! $unit->status->canTransitionTo(UnitStatus::Reserved)) {
            throw new RuntimeException(
                "Cannot reserve unit [{$unit->serial_number}]: current status is {$unit->status->value}."
            );
        }

        $unit->status = UnitStatus::Reserved;
        $unit->save();

        return $unit;
    }

    /**
     * Releases a previously reserved serialized unit back to InStock.
     */
    public function releaseSerializedReservation(int $productUnitId): ProductUnit
    {
        $unit = ProductUnit::withoutGlobalScopes()
            ->where('id', $productUnitId)
            ->lockForUpdate()
            ->firstOrFail();

        if ($unit->status !== UnitStatus::Reserved) {
            throw new RuntimeException(
                "Cannot release reservation on unit [{$unit->serial_number}]: current status is "
                . "{$unit->status->value}, expected reserved."
            );
        }

        $unit->status = UnitStatus::InStock;
        $unit->save();

        return $unit;
    }
}