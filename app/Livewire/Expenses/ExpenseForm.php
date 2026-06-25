<?php

namespace App\Livewire\Expenses;

use App\Actions\RecordExpenseAction;
use App\Models\Branch;
use App\Models\ExpenseCategory;
use App\Models\PaymentAccount;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use \App\Traits\HasAuthorization;

#[Layout('components.layouts.app')]
#[Title('New Expense')]
class ExpenseForm extends Component
{
    use WithFileUploads;
    use HasAuthorization;

    public int    $categoryId       = 0;
    public int    $paymentAccountId = 0;
    public int    $branchId         = 0;
    public string $amount           = '';
    public string $expenseDate      = '';
    public string $description      = '';
    public string $referenceNumber  = '';
    public string $notes            = '';
    public $receipt                 = null;

    public function mount(): void
    {
        $this->requirePermission('expenses.create');
        $this->expenseDate = now()->format('Y-m-d');
        $this->branchId    = (int) (
            Auth::user()->branch_id
            ?? Branch::where('shop_id', Auth::user()->shop_id)->where('is_main', true)->value('id')
            ?? 0
        );
    }

    #[Computed]
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return ExpenseCategory::where('is_active', true)
            ->orderBy('parent_id')->orderBy('name')->get();
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('is_active', true)->orderBy('provider')->get();
    }

    #[Computed]
    public function branches(): \Illuminate\Database\Eloquent\Collection
    {
        return Branch::where('shop_id', Auth::user()->shop_id)->where('is_active', true)->get();
    }

    public function save(RecordExpenseAction $action): void
    {
        $this->validate([
            'categoryId'       => 'required|integer|min:1',
            'paymentAccountId' => 'required|integer|min:1',
            'branchId'         => 'required|integer|min:1',
            'amount'           => 'required|numeric|min:0.01',
            'expenseDate'      => 'required|date|before_or_equal:today',
            'description'      => 'required|string|max:255',
            'receipt'          => 'nullable|image|max:3072',
        ], [
            'categoryId.min'       => 'Please select a category.',
            'paymentAccountId.min' => 'Please select a payment account.',
        ]);

        $receiptPath = $this->receipt
            ? $this->receipt->store("shops/" . Auth::user()->shop_id . "/expenses", 'public')
            : null;

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);

        try {
            $action->execute($shop, [
                'expense_category_id' => $this->categoryId,
                'payment_account_id'  => $this->paymentAccountId,
                'branch_id'           => $this->branchId,
                'amount'              => (float) $this->amount,
                'expense_date'        => $this->expenseDate,
                'description'         => $this->description,
                'reference_number'    => $this->referenceNumber ?: null,
                'notes'               => $this->notes ?: null,
                'receipt_path'        => $receiptPath,
            ], Auth::user());

            $this->dispatch('notify', ['type' => 'success', 'message' => "Expense ৳{$this->amount} recorded."]);
            $this->redirect(route('expenses.index'), navigate: true);

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.expenses.expense-form');
    }
}