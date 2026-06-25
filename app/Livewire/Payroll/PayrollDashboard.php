<?php
namespace App\Livewire\Payroll;

use App\Actions\ProcessPayrollAction;
use App\Actions\RecordSalaryDrawAction;
use App\Enums\PayrollStatus;
use App\Enums\SalaryDrawType;
use App\Enums\UserType;
use App\Models\PaymentAccount;
use App\Models\PayrollRun;
use App\Models\SalaryDraw;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Payroll')]
class PayrollDashboard extends Component
{
    use WithPagination;
    use \App\Traits\HasAuthorization;


    // ── Generate payroll ──────────────────────────────────────────────────────
    public int    $generateYear    = 0;
    public int    $generateMonth   = 0;
    public bool   $showGenerate    = false;

    // ── Pay modal ─────────────────────────────────────────────────────────────
    public bool   $showPayModal    = false;
    public ?int   $payingRunId     = null;
    public int    $defaultPayAccId = 0;

    // ── Salary Draw form ──────────────────────────────────────────────────────
    public bool   $showDrawForm    = false;
    public int    $drawEmployeeId  = 0;
    public string $drawAmount      = '';
    public int    $drawPayAccId    = 0;
    public string $drawDate        = '';
    public int    $drawYear        = 0;
    public int    $drawMonth       = 0;
    public string $drawType        = 'salary';
    public string $drawNotes       = '';

    public function mount(): void
    {
        $this->requirePermission('payroll.view');
        $this->generateYear  = (int) now()->format('Y');
        $this->generateMonth = (int) now()->format('m');
        $this->drawYear      = (int) now()->format('Y');
        $this->drawMonth     = (int) now()->format('m');
        $this->drawDate      = now()->format('Y-m-d');
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('is_active', true)->get();
    }

    #[Computed]
    public function employees(): \Illuminate\Database\Eloquent\Collection
    {
        return User::withoutGlobalScopes()
            ->where('shop_id', Auth::user()->shop_id)
            ->where('user_type', UserType::Employee->value)
            ->where('is_active', true)
            ->with('employeeProfile')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function currentMonthDraws(): \Illuminate\Support\Collection
    {
        $year  = (int) now()->format('Y');
        $month = (int) now()->format('m');
        $shopId = Auth::user()->shop_id;

        return SalaryDraw::withoutGlobalScopes()
            ->where('shop_id', $shopId)
            ->where('for_year', $year)
            ->where('for_month', $month)
            ->with(['user', 'paymentAccount'])
            ->latest('draw_date')
            ->get()
            ->groupBy('user_id');
    }

    // ── Salary Draw ───────────────────────────────────────────────────────────

    public function openDrawForm(?int $employeeId = null): void
    {
        $this->showDrawForm   = true;
        $this->drawEmployeeId = $employeeId ?? 0;
        $this->drawAmount     = '';
        $this->drawNotes      = '';
    }

    public function recordDraw(RecordSalaryDrawAction $action): void
    {
        $this->validate([
            'drawEmployeeId' => 'required|integer|min:1',
            'drawAmount'     => 'required|numeric|min:1',
            'drawPayAccId'   => 'required|integer|min:1',
            'drawDate'       => 'required|date',
            'drawYear'       => 'required|integer|min:2020',
            'drawMonth'      => 'required|integer|min:1|max:12',
        ], [
            'drawEmployeeId.min' => 'Please select an employee.',
            'drawPayAccId.min'   => 'Please select a payment account.',
        ]);

        $shop     = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        $employee = User::withoutGlobalScopes()->findOrFail($this->drawEmployeeId);

        try {
            $draw = $action->execute($shop, $employee, [
                'amount'             => (float) $this->drawAmount,
                'payment_account_id' => $this->drawPayAccId,
                'draw_date'          => $this->drawDate,
                'for_year'           => $this->drawYear,
                'for_month'          => $this->drawMonth,
                'draw_type'          => $this->drawType,
                'notes'              => $this->drawNotes ?: null,
            ], Auth::user());

            $this->showDrawForm = false;
            unset($this->currentMonthDraws);
            $this->dispatch('notify', ['type' => 'success',
                'message' => "৳{$draw->amount} salary draw recorded for {$employee->name}."]);

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ── Generate Payroll ──────────────────────────────────────────────────────

    public function generate(ProcessPayrollAction $action): void
    {
        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        try {
            $run = $action->generateDraft($shop, $this->generateYear, $this->generateMonth, Auth::user());
            $this->showGenerate = false;
            $this->dispatch('notify', ['type' => 'success',
                'message' => "Draft payroll generated for {$run->monthName()}."]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function deleteDraft(int $runId, ProcessPayrollAction $action): void
    {
        $run = PayrollRun::findOrFail($runId);
        try {
            $action->deleteDraft($run);
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Draft payroll deleted.']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function approve(int $runId, ProcessPayrollAction $action): void
    {
        $run = PayrollRun::findOrFail($runId);
        try {
            $action->approve($run, Auth::user());
            $this->dispatch('notify', ['type' => 'success', 'message' => 'Payroll approved.']);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function openPayModal(int $runId): void
    {
        $this->payingRunId  = $runId;
        $this->showPayModal = true;
    }

    public function pay(ProcessPayrollAction $action): void
    {
        $this->validate(['defaultPayAccId' => 'required|integer|min:1'],
            ['defaultPayAccId.min' => 'Select a payment account.']);

        $run = PayrollRun::findOrFail($this->payingRunId);
        try {
            $action->pay($run, $this->defaultPayAccId, Auth::user());
            $this->showPayModal = false;
            $this->dispatch('notify', ['type' => 'success',
                'message' => "Remaining salaries paid — ৳" . number_format($run->total_net, 2)]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        $runs = PayrollRun::with(['items.user'])->latest()->paginate(10);
        return view('livewire.payroll.payroll-dashboard', compact('runs'));
    }
}