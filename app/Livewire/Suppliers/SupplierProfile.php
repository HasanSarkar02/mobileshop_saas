<?php

namespace App\Livewire\Suppliers;

use App\Actions\RecordSupplierPaymentAction;
use App\Models\Branch;
use App\Models\PaymentAccount;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Traits\HasAuthorization;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Supplier Profile')]
class SupplierProfile extends Component
{
    use WithPagination, HasAuthorization;

    public Supplier $supplier;

    #[Url]
    public string $activeTab = 'overview';

    // Payment form
    public bool   $showPaymentForm  = false;
    public string $payAmount        = '';
    public int    $payAccountId     = 0;
    public string $payDate          = '';
    public string $payMethod        = 'cash';
    public string $payReference     = '';
    public string $payNotes         = '';
    public int    $payBranchId      = 0;

    public function mount(Supplier $supplier): void
    {
         $this->requirePermission('suppliers.manage');
        if ($supplier->shop_id !== Auth::user()->shop_id) {
            abort(403);
        }

        $this->supplier    = $supplier;
        $this->payDate     = now()->format('Y-m-d');
        $this->payBranchId = (int) (
            Auth::user()->branch_id
            ?? Branch::where('shop_id', Auth::user()->shop_id)->where('is_main', true)->value('id')
            ?? 0
        );
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)->get();
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('shop_id', Auth::user()->shop_id)->where('is_active', true)->get();
    }

    /**
     * Full ledger — all purchases + payments chronologically with running balance.
     */
    #[Computed]
    public function ledger(): \Illuminate\Support\Collection
    {
        $shopId     = Auth::user()->shop_id;
        $supplierId = $this->supplier->id;

        // All purchases
        $purchases = DB::table('purchases')
            ->where('shop_id', $shopId)
            ->where('supplier_id', $supplierId)
            ->whereIn('payment_status', ['unpaid', 'partial', 'paid'])
            ->selectRaw("
                purchase_date AS txn_date,
                'Purchase'    AS txn_type,
                reference_number AS reference,
                total_amount  AS debit,
                0             AS credit,
                id            AS ref_id,
                'purchase'    AS ref_type
            ")
            ->get();

        // All payments
        $payments = DB::table('supplier_payments')
            ->where('shop_id', $shopId)
            ->where('supplier_id', $supplierId)
            ->selectRaw("
                payment_date  AS txn_date,
                'Payment'     AS txn_type,
                reference_number AS reference,
                0             AS debit,
                amount        AS credit,
                id            AS ref_id,
                'supplier_payment' AS ref_type
            ")
            ->get();

        // All purchase returns (credit notes)
        $returns = DB::table('purchase_returns')
            ->where('shop_id', $shopId)
            ->where('supplier_id', $supplierId)
            ->selectRaw("
                return_date   AS txn_date,
                'Return'      AS txn_type,
                return_number AS reference,
                0             AS debit,
                total_amount  AS credit,
                id            AS ref_id,
                'purchase_return' AS ref_type
            ")
            ->get();

        // Merge + sort + running balance
        $all     = $purchases->concat($payments)->concat($returns)
            ->sortBy('txn_date');

        $running = 0.0;
        return $all->map(function ($row) use (&$running) {
            $running += (float) $row->debit - (float) $row->credit;
            $row->running_balance = $running;
            return $row;
        })->values();
    }

    #[Computed]
    public function agingSummary(): array
    {
        $shopId     = Auth::user()->shop_id;
        $supplierId = $this->supplier->id;
        $today      = now()->toDateString();

        $unpaid = DB::table('purchases')
            ->where('shop_id', $shopId)
            ->where('supplier_id', $supplierId)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->selectRaw('purchase_date, total_amount, DATEDIFF(?, purchase_date) AS days_overdue', [$today])
            ->get();

        $buckets = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0];

        foreach ($unpaid as $p) {
            $days = (int) $p->days_overdue;
            $amt  = (float) $p->total_amount;

            if ($days <= 0)        $buckets['current'] += $amt;
            elseif ($days <= 30)   $buckets['1_30']    += $amt;
            elseif ($days <= 60)   $buckets['31_60']   += $amt;
            elseif ($days <= 90)   $buckets['61_90']   += $amt;
            else                   $buckets['over_90'] += $amt;
        }

        return $buckets;
    }

    public function recordPayment(RecordSupplierPaymentAction $action): void
    {
        $this->requirePermission('suppliers.payment');
        $this->validate([
            'payAmount'    => 'required|numeric|min:0.01',
            'payAccountId' => 'required|integer|min:1',
            'payDate'      => 'required|date',
        ], ['payAccountId.min' => 'Please select a payment account.']);

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

        try {
            // Pass calculated balance to action instead of stale denormalized field
            $ledger            = $this->ledger;
            $calculatedBalance = $ledger->isNotEmpty()
                ? (float) $ledger->last()->running_balance
                : (float) $this->supplier->current_balance;

            if ((float) $this->payAmount > $calculatedBalance + 0.01) {
                throw new \RuntimeException(
                    "Payment ৳" . number_format((float) $this->payAmount, 2) .
                    " exceeds outstanding balance ৳" . number_format($calculatedBalance, 2)
                );
            }

            $action->execute($shop, $this->supplier, [
                'amount'             => (float) $this->payAmount,
                'payment_account_id' => $this->payAccountId,
                'payment_date'       => $this->payDate,
                'payment_method'     => $this->payMethod,
                'reference_number'   => $this->payReference ?: null,
                'notes'              => $this->payNotes ?: null,
                'branch_id'          => $this->payBranchId,
            ], Auth::user());

            $this->supplier->refresh();
            $this->showPaymentForm = false;
            $this->payAmount = $this->payReference = '';
            unset($this->ledger);

            $this->dispatch('notify', ['type' => 'success',
                'message' => "Payment of ৳{$this->payAmount} recorded successfully."]);

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $purchases = \App\Models\Purchase::with(['lineItems.variant.product', 'branch'])
            ->where('supplier_id', $this->supplier->id)
            ->latest('purchase_date')
            ->paginate(10, pageName: 'pPage');

        $payments = SupplierPayment::with(['paymentAccount'])
            ->where('supplier_id', $this->supplier->id)
            ->latest('payment_date')
            ->paginate(10, pageName: 'pyPage');

        return view('livewire.suppliers.supplier-profile', compact('purchases', 'payments'));
    }
}