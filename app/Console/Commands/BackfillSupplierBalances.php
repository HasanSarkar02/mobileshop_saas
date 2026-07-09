<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillSupplierBalances extends Command
{
    protected $signature   = 'suppliers:backfill-balances';
    protected $description = 'Recalculate current_balance for all suppliers from purchases and payments.';

    public function handle(): void
    {
        $suppliers = DB::table('suppliers')->get(['id', 'name', 'shop_id']);

        foreach ($suppliers as $supplier) {
            // Total from all unpaid/partial purchases
            $totalPurchased = (float) DB::table('purchases')
                ->where('supplier_id', $supplier->id)
                ->whereIn('payment_status', ['unpaid', 'partial'])
                ->sum('total_amount');

            // Total paid via supplier_payments table
            $totalPaid = (float) DB::table('supplier_payments')
                ->where('supplier_id', $supplier->id)
                ->sum('amount');

            // Total returned (credit notes reduce what we owe)
            $totalReturned = (float) DB::table('purchase_returns')
                ->where('supplier_id', $supplier->id)
                ->where('settlement_type', 'credit_note')
                ->sum('total_amount');

            $balance = max(0, $totalPurchased - $totalPaid - $totalReturned);

            DB::table('suppliers')
                ->where('id', $supplier->id)
                ->update(['current_balance' => $balance]);

            $this->line("✓ {$supplier->name} → ৳" . number_format($balance, 2));
        }

        $this->info('Supplier balances recalculated.');
    }
}