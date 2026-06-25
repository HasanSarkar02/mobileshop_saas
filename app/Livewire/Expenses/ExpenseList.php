<?php

namespace App\Livewire\Expenses;

use App\Actions\ApproveExpenseAction;
use App\Actions\VoidExpenseAction;
use App\Enums\ExpenseStatus;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use \App\Traits\HasAuthorization;

#[Layout('components.layouts.app')]
#[Title('Expenses')]
class ExpenseList extends Component
{
    use WithPagination;
    use HasAuthorization;

    #[Url(as: 'q')]
    public string $search      = '';

    #[Url]
    public int    $category    = 0;

    #[Url]
    public string $status      = '';

    #[Url]
    public string $dateFrom    = '';

    #[Url]
    public string $dateTo      = '';

    // Void modal
    public bool   $showVoidModal  = false;
    public ?int   $voidExpenseId  = null;
    public string $voidReason     = '';

    // Reject modal
    public bool   $showRejectModal   = false;
    public ?int   $rejectExpenseId   = null;
    public string $rejectionReason   = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingStatus(): void { $this->resetPage(); }
    public function updatingCategory(): void { $this->resetPage(); }

    #[Computed]
    public function pendingCount(): int
    {
        return Expense::where('status', ExpenseStatus::Pending)->count();
    }

    #[Computed]
    public function monthSummary(): array
    {
        $start = now()->startOfMonth();
        $end   = now()->endOfMonth();

        $expenses = Expense::where('status', ExpenseStatus::Approved)
            ->whereBetween('expense_date', [$start, $end])
            ->with('category')
            ->get();

        $byCategory = $expenses
            ->groupBy(fn ($e) => $e->category?->name ?? 'Uncategorized')
            ->map(fn ($g) => (float) $g->sum('amount'))
            ->sortByDesc(fn ($v) => $v)
            ->take(5);

        return [
            'total'       => (float) $expenses->sum('amount'),
            'by_category' => $byCategory,
        ];
    }

    public function mount(): void
    {
        $this->requirePermission('expenses.view');
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function approve(int $expenseId, ApproveExpenseAction $action): void
    {
        $expense = Expense::findOrFail($expenseId);

        try {
            $action->approve($expense, Auth::user());
            $this->dispatch('notify', ['type' => 'success',
                'message' => "Expense ৳{$expense->amount} approved."]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function openRejectModal(int $expenseId): void
    {
        $this->rejectExpenseId = $expenseId;
        $this->rejectionReason = '';
        $this->showRejectModal = true;
    }

    public function reject(ApproveExpenseAction $action): void
    {
        $this->validate(['rejectionReason' => 'required|string|min:3']);

        $expense = Expense::findOrFail($this->rejectExpenseId);

        try {
            $action->reject($expense, $this->rejectionReason, Auth::user());
            $this->showRejectModal = false;
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'Expense rejected.']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ── Void ──────────────────────────────────────────────────────────────────

    public function openVoidModal(int $expenseId): void
    {
        $this->voidExpenseId = $expenseId;
        $this->voidReason    = '';
        $this->showVoidModal = true;
    }

    public function void(VoidExpenseAction $action): void
    {
        $this->validate(['voidReason' => 'required|string|min:5']);

        $expense = Expense::findOrFail($this->voidExpenseId);

        try {
            $action->execute($expense, $this->voidReason, Auth::user());
            $this->showVoidModal = false;
            $this->dispatch('notify', ['type' => 'warning', 'message' => 'Expense voided.']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $expenses = Expense::with(['category.parent', 'paymentAccount', 'createdBy', 'branch'])
            ->when($this->search, fn ($q) =>
                $q->where('description', 'like', "%{$this->search}%")
                  ->orWhere('reference_number', 'like', "%{$this->search}%")
            )
            ->when($this->category, fn ($q) => $q->where('expense_category_id', $this->category))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when(! $this->status, fn ($q) => $q->whereNotIn('status', [ExpenseStatus::Voided->value]))
            ->when($this->dateFrom, fn ($q) => $q->where('expense_date', '>=', $this->dateFrom))
            ->when($this->dateTo,   fn ($q) => $q->where('expense_date', '<=', $this->dateTo))
            ->latest('expense_date')
            ->paginate(25);

        $categories = ExpenseCategory::where('is_active', true)->orderBy('name')->get();

        return view('livewire.expenses.expense-list', compact('expenses', 'categories'));
    }
}