<?php

namespace App\Services;

use App\Enums\UnitStatus;
use App\Models\ProductUnit;
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
}