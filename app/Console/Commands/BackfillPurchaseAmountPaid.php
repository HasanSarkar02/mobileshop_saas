<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillPurchaseAmountPaid extends Command
{
    protected $signature   = 'purchases:backfill-amount-paid';
    protected $description = 'Recalculate amount_paid on purchases from supplier_payments using FIFO.';

    public function handle(): void
    {
        $shops = DB::table('shops')->pluck('id');

        foreach ($shops as $shopId) {
            $suppliers = DB::table('purchases')
                ->where('shop_id', $shopId)
                ->distinct()
                ->pluck('supplier_id');

            foreach ($suppliers as $supplierId) {
                // Reset all purchases for this supplier to unpaid first
                DB::table('purchases')
                    ->where('shop_id', $shopId)
                    ->where('supplier_id', $supplierId)
                    ->update(['amount_paid' => 0, 'payment_status' => 'unpaid']);

                // Get total paid to this supplier
                $totalPaid = (float) DB::table('supplier_payments')
                    ->where('shop_id', $shopId)
                    ->where('supplier_id', $supplierId)
                    ->sum('amount');

                if ($totalPaid <= 0) continue;

                // Apply FIFO
                $purchases = DB::table('purchases')
                    ->where('shop_id', $shopId)
                    ->where('supplier_id', $supplierId)
                    ->orderBy('purchase_date')
                    ->orderBy('id')
                    ->get(['id', 'total_amount']);

                $remaining = $totalPaid;

                foreach ($purchases as $p) {
                    if ($remaining <= 0) break;

                    $outstanding = (float) $p->total_amount;

                    if ($remaining >= $outstanding) {
                        DB::table('purchases')->where('id', $p->id)->update([
                            'amount_paid'    => $outstanding,
                            'payment_status' => 'paid',
                        ]);
                        $remaining -= $outstanding;
                    } else {
                        DB::table('purchases')->where('id', $p->id)->update([
                            'amount_paid'    => round($remaining, 2),
                            'payment_status' => 'partial',
                        ]);
                        $remaining = 0;
                    }
                }
            }

            $this->line("✓ Shop {$shopId}");
        }

        $this->info('Done.');
    }
}