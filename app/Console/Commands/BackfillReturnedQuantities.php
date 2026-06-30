<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillReturnedQuantities extends Command
{
    protected $signature = 'sales:backfill-returned-quantities';
    protected $description = 'Populate sale_items.returned_quantity from existing completed credit notes (one-time data fix).';

    public function handle(): void
    {
        $rows = DB::table('credit_note_items')
            ->join('credit_notes', 'credit_notes.id', '=', 'credit_note_items.credit_note_id')
            ->where('credit_notes.status', 'completed')
            ->select(
                'credit_note_items.original_sale_item_id',
                DB::raw('SUM(credit_note_items.quantity) as total_returned')
            )
            ->groupBy('credit_note_items.original_sale_item_id')
            ->get();

        $updated = 0;

        foreach ($rows as $row) {
            $saleItem = DB::table('sale_items')->where('id', $row->original_sale_item_id)->first();
            if (! $saleItem) continue;

            // Clamp so it can never exceed the original quantity (safety net)
            $clamped = min((int) $row->total_returned, (int) $saleItem->quantity);

            DB::table('sale_items')
                ->where('id', $row->original_sale_item_id)
                ->update(['returned_quantity' => $clamped]);

            $updated++;
        }

        // Mark sales as return_processed if any of their items had a return,
        // in case that flag was missed historically.
        $saleIds = DB::table('sale_items')
            ->where('returned_quantity', '>', 0)
            ->pluck('sale_id')
            ->unique();

        DB::table('sales')->whereIn('id', $saleIds)->update(['return_processed' => true]);

        $this->info("Backfilled {$updated} sale item(s) across {$saleIds->count()} sale(s).");
    }
}