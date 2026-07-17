<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use App\Models\Supplier;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Supplier Ledger')]
class SupplierLedgerReport extends Component
{
    use HasReportFilter;
    use \App\Traits\HasAuthorization;

    public function mount(): void
{
    $this->requirePermission('reports.financial');
}
    #[Url(as: 'supplier')]
    public int $supplierId = 0;

    #[Computed]
    public function suppliers(): \Illuminate\Database\Eloquent\Collection
    {
        return Supplier::where('shop_id', Auth::user()->shop_id)
            ->orderBy('name')->get(['id', 'name', 'current_balance']);
    }

    #[Computed]
    public function ledger(): ?object
    {
        if (! $this->supplierId) return null;

        $filter     = $this->buildFilter();
        $supplierId = $this->supplierId;
        $shopId     = Auth::user()->shop_id;

        $supplier = Supplier::where('shop_id', $shopId)->findOrFail($supplierId);

        // All transactions before period — opening balance
        $openingPurchases = (float) DB::table('purchases')
            ->where('supplier_id', $supplierId)
            ->where('shop_id', $shopId)
            ->where('purchase_date', '<', $filter->dateRange->from->toDateString())
            ->sum('total_amount');

        $openingPayments = (float) DB::table('supplier_payments')
            ->where('supplier_id', $supplierId)
            ->where('shop_id', $shopId)
            ->where('payment_date', '<', $filter->dateRange->from->toDateString())
            ->sum('amount');

        $openingReturns = (float) DB::table('purchase_returns')
            ->where('supplier_id', $supplierId)
            ->where('shop_id', $shopId)
            ->where('settlement_type', 'credit_note')
            ->where('return_date', '<', $filter->dateRange->from->toDateString())
            ->sum('total_amount');

        $opening = $openingPurchases - $openingPayments - $openingReturns;

        // Period transactions
        $purchases = DB::table('purchases')
            ->where('supplier_id', $supplierId)->where('shop_id', $shopId)
            ->whereBetween('purchase_date', [$filter->dateRange->from->toDateString(), $filter->dateRange->to->toDateString()])
            ->selectRaw("purchase_date AS txn_date,'Purchase' AS txn_type, reference_number AS ref, total_amount AS debit, 0 AS credit")
            ->get();

        $payments = DB::table('supplier_payments')
            ->where('supplier_id', $supplierId)->where('shop_id', $shopId)
            ->whereBetween('payment_date', [$filter->dateRange->from->toDateString(), $filter->dateRange->to->toDateString()])
            ->selectRaw("payment_date AS txn_date,'Payment' AS txn_type, payment_number AS ref, 0 AS debit, amount AS credit")
            ->get();

        $returns = DB::table('purchase_returns')
            ->where('supplier_id', $supplierId)->where('shop_id', $shopId)
            ->whereBetween('return_date', [$filter->dateRange->from->toDateString(), $filter->dateRange->to->toDateString()])
            ->selectRaw("return_date AS txn_date,'Return' AS txn_type, return_number AS ref, 0 AS debit, total_amount AS credit")
            ->get();

        $running = $opening;
        $lines   = $purchases->concat($payments)->concat($returns)
            ->sortBy('txn_date')
            ->map(function ($r) use (&$running) {
                $running += (float)$r->debit - (float)$r->credit;
                $r->balance = $running;
                return $r;
            })->values();

        return (object) [
            'supplier' => $supplier,
            'opening'  => $opening,
            'lines'    => $lines,
            'closing'  => $running,
            'total_dr' => (float) $lines->sum('debit'),
            'total_cr' => (float) $lines->sum('credit'),
        ];
    }

    public function render()
    {
        return view('livewire.reports.supplier-ledger-report', [
            'periodLabel' => $this->periodLabel(),
        ]);
    }
}