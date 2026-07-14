<?php

namespace App\Livewire\Payroll;

use App\Actions\Payroll\DisburseLoanAction;
use App\Enums\PayrollAuditAction;
use App\Models\Account;
use App\Models\PaymentAccount;
use App\Models\PayrollAuditLog;
use App\Models\PayrollLoan;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Employee Loans & Advances')]
class PayrollLoanList extends Component
{
    use \App\Traits\HasAuthorization, WithPagination;

    #[Url]
    public string $statusFilter = 'active';

    #[Url]
    public string $search = '';

    // New Loan Form
    public bool   $showForm           = false;
    public int    $selectedUserId     = 0;
    public string $loanType           = 'advance';
    public string $amount             = '';
    public string $monthlyDeduction   = '';
    public string $disbursementDate   = '';
    public int    $paymentAccountId   = 0;
    public string $purpose            = '';
    public string $notes              = '';

    // Waiver modal
    public bool   $showWaiverModal    = false;
    public ?int   $waivingLoanId      = null;
    public string $waiverReason       = '';

    public function mount(): void
    {
        $this->requirePermission('payroll.manage_loans');
        $this->disbursementDate = now()->format('Y-m-d');
    }

    #[Computed]
    public function loans()
    {
        return PayrollLoan::where('shop_id', Auth::user()->shop_id)
            ->with(['user', 'disbursementAccount'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->search, fn ($q) =>
                $q->whereHas('user', fn ($u) => $u->where('name', 'like', "%{$this->search}%"))
            )
            ->orderByDesc('disbursement_date')
            ->paginate(20);
    }

    #[Computed]
    public function stats(): object
    {
        $shopId = Auth::user()->shop_id;
        return (object) [
            'total_active'      => PayrollLoan::where('shop_id', $shopId)->where('status', 'active')->count(),
            'total_outstanding' => (float) PayrollLoan::where('shop_id', $shopId)->where('status', 'active')->sum('outstanding_balance'),
            'total_disbursed'   => (float) PayrollLoan::where('shop_id', $shopId)->sum('total_amount'),
            'fully_recovered'   => PayrollLoan::where('shop_id', $shopId)->where('status', 'fully_recovered')->count(),
        ];
    }

    #[Computed]
    public function employees(): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->where('user_type', 'employee')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->get();
    }

    public function disburse(DisburseLoanAction $action): void
    {
        $this->validate([
            'selectedUserId'   => 'required|integer|min:1',
            'loanType'         => 'required|in:advance,loan',
            'amount'           => 'required|numeric|min:1',
            'monthlyDeduction' => 'required|numeric|min:1',
            'disbursementDate' => 'required|date',
            'paymentAccountId' => 'required|integer|min:1',
        ], [
            'selectedUserId.min'   => 'Please select an employee.',
            'paymentAccountId.min' => 'Please select a payment account.',
        ]);

        $employee = User::where('shop_id', Auth::user()->shop_id)
            ->findOrFail($this->selectedUserId);

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

        try {
            $loan = $action->execute($shop, $employee, [
                'loan_type'          => $this->loanType,
                'amount'             => (float) $this->amount,
                'monthly_deduction'  => (float) $this->monthlyDeduction,
                'disbursement_date'  => $this->disbursementDate,
                'payment_account_id' => $this->paymentAccountId,
                'purpose'            => $this->purpose ?: null,
                'notes'              => $this->notes ?: null,
            ], Auth::user());

            $this->resetForm();
            unset($this->loans, $this->stats);
            $type = $loan->loan_type === 'advance' ? 'Advance' : 'Loan';
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $type .
                    " of ৳" . number_format($loan->total_amount, 0) .
                    " disbursed to {$employee->name}. ({$loan->loan_number})"
            ]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function openWaiver(int $loanId): void
    {
        $this->waivingLoanId = $loanId;
        $this->waiverReason  = '';
        $this->showWaiverModal = true;
    }

    public function waive(AccountingService $accounting): void
    {
        $this->validate(['waiverReason' => 'required|string|min:10']);

        $loan = PayrollLoan::where('shop_id', Auth::user()->shop_id)
            ->where('status', 'active')
            ->findOrFail($this->waivingLoanId);

        $waiveAmount = (float) $loan->outstanding_balance;

        if ($waiveAmount <= 0) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => 'Nothing to waive — balance is already zero.']);
            return;
        }

        try {
            DB::transaction(function () use ($loan, $waiveAmount, $accounting) {
                $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

                // GL: Dr Salary Advance Receivable (1150) → remove asset
                //     Cr Miscellaneous Income (4050)     → income on waiver
                $advGl = Account::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->where('code', '1150')
                    ->firstOrFail();

                $miscGl = Account::withoutGlobalScopes()
                    ->where('shop_id', $shop->id)
                    ->where('code', '4050')
                    ->firstOrFail();

                $journal = $accounting->postEntry(
                    shop:        $shop,
                    description: "Loan waiver — {$loan->user?->name} ({$loan->loan_number})",
                    lines: [
                        ['account_id' => $miscGl->id,  'debit'  => $waiveAmount,
                         'description' => "Waiver expense — {$loan->loan_number}"],
                        ['account_id' => $advGl->id,   'credit' => $waiveAmount,
                         'description' => "Advance receivable reduced — {$loan->loan_number}"],
                    ],
                    entryDate: now()->toDateTime(),
                    reference: $loan,
                    branchId:  $loan->user?->branch_id,
                    actor:     Auth::user(),
                );

                $loan->update([
                    'outstanding_balance'       => 0,
                    'waived_amount'             => $waiveAmount,
                    'waived_by'                 => Auth::id(),
                    'waived_at'                 => now(),
                    'waiver_reason'             => $this->waiverReason,
                    'waiver_journal_entry_id'   => $journal->id,
                    'status'                    => 'waived',
                ]);

                PayrollAuditLog::record(
                    shopId:        $shop->id,
                    referenceType: 'payroll_loans',
                    referenceId:   $loan->id,
                    action:        PayrollAuditAction::LoanWaived,
                    amount:        $waiveAmount,
                    reason:        $this->waiverReason,
                );
            });

            $this->showWaiverModal = false;
            unset($this->loans, $this->stats);
            $this->dispatch('notify', ['type' => 'success',
                'message' => "Loan {$loan->loan_number} balance of ৳" .
                             number_format($waiveAmount, 0) . " waived."]);

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function resetForm(): void
    {
        $this->showForm         = false;
        $this->selectedUserId   = 0;
        $this->loanType         = 'advance';
        $this->amount           = '';
        $this->monthlyDeduction = '';
        $this->disbursementDate = now()->format('Y-m-d');
        $this->paymentAccountId = 0;
        $this->purpose          = '';
        $this->notes            = '';
    }

    public function render()
    {
        return view('livewire.payroll.payroll-loan-list');
    }
}