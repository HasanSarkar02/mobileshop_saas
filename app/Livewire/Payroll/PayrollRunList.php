<?php

namespace App\Livewire\Payroll;

use App\Actions\Payroll\ApprovePayrollRunAction;
use App\Actions\Payroll\CancelPayrollRunAction;
use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Payroll Runs')]
class PayrollRunList extends Component
{
    use \App\Traits\HasAuthorization, WithPagination;

    #[Url]
    public string $status    = '';

    #[Url]
    public string $yearMonth = '';

    // Cancel modal
    public bool   $showCancelModal  = false;
    public ?int   $cancellingRunId  = null;
    public string $cancelReason     = '';

    public function mount(): void
    {
        $this->requirePermission('payroll.view');

        if (! $this->yearMonth) {
            $this->yearMonth = now()->format('Y-m');
        }
    }

    #[Computed]
    public function runs()
    {
        [$year, $month] = array_map('intval', explode('-', $this->yearMonth . '-0'));

        return PayrollRun::where('shop_id', Auth::user()->shop_id)
            ->with(['branch', 'department', 'generatedBy', 'approvedBy'])
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($year,  fn ($q) => $q->where('year', $year))
            ->when($month, fn ($q) => $q->where('month', $month))
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->orderByDesc('id')
            ->paginate(20);
    }

    #[Computed]
    public function statuses(): array
    {
        return PayrollRunStatus::cases();
    }

    public function quickApprove(int $runId, ApprovePayrollRunAction $action): void
    {
        $this->requirePermission('payroll.approve');

        $run = PayrollRun::where('shop_id', Auth::user()->shop_id)->findOrFail($runId);

        try {
            $action->execute($run, Auth::user());
            $this->dispatch('notify', ['type' => 'success',
                'message' => "{$run->run_number} approved. Journal posted."]);
            unset($this->runs);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function openCancelModal(int $id): void
    {
        $this->cancellingRunId = $id;
        $this->cancelReason    = '';
        $this->showCancelModal = true;
    }

    public function cancel(CancelPayrollRunAction $action): void
    {
        $this->validate(['cancelReason' => 'required|string|min:5']);

        $run = PayrollRun::where('shop_id', Auth::user()->shop_id)
            ->findOrFail($this->cancellingRunId);

        try {
            $action->execute($run, $this->cancelReason, Auth::user());
            $this->showCancelModal = false;
            $this->dispatch('notify', ['type' => 'warning', 'message' => "{$run->run_number} cancelled."]);
            unset($this->runs);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.payroll.payroll-run-list');
    }
}