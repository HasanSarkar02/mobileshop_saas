<?php

namespace App\Livewire\Payroll;

use App\Actions\Payroll\ApprovePayrollRunAction;
use App\Actions\Payroll\CancelPayrollRunAction;
use App\Actions\Payroll\ReversePayrollRunAction;
use App\Actions\Payroll\SubmitPayrollForReviewAction;
use App\Enums\PayrollRunStatus;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Payroll Run')]
class PayrollRunDetail extends Component
{
    use \App\Traits\HasAuthorization;

    public PayrollRun $run;

    // Cancel modal
    public bool   $showCancelModal  = false;
    public string $cancelReason     = '';

    // Reverse modal
    public bool   $showReverseModal = false;
    public string $reversalReason   = '';

    public function mount(PayrollRun $run): void
    {
        $this->requirePermission('payroll.view');

        if ($run->shop_id !== Auth::user()->shop_id) abort(403);

        $this->run = $run->load([
            'slips.user',
            'slips.payments.paymentAccount',
            'branch', 'department',
            'generatedBy', 'approvedBy', 'cancelledBy', 'reversedBy',
            'journalEntry.lines.account',
        ]);
    }

    #[Computed]
    public function canSubmit(): bool
    {
        return $this->run->status === PayrollRunStatus::Draft
            && Auth::user()->can('payroll.review');
    }

    #[Computed]
    public function canApprove(): bool
    {
        return $this->run->status === PayrollRunStatus::UnderReview
            && Auth::user()->can('payroll.approve');
    }

    #[Computed]
    public function canCancel(): bool
    {
        return $this->run->status->canBeCancelled();
    }

    #[Computed]
    public function canReverse(): bool
    {
        return $this->run->status === PayrollRunStatus::Approved
            && Auth::user()->can('payroll.reverse');
    }

    public function submit(SubmitPayrollForReviewAction $action): void
    {
        $this->requirePermission('payroll.review');

        try {
            $action->execute($this->run, Auth::user());
            $this->run->refresh();
            $this->dispatch('notify', ['type' => 'success',
                'message' => "{$this->run->run_number} submitted for Owner approval."]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function approve(ApprovePayrollRunAction $action): void
    {
        $this->requirePermission('payroll.approve');

        try {
            $action->execute($this->run, Auth::user());
            $this->run->refresh()->load(['journalEntry.lines.account', 'approvedBy', 'slips']);
            $this->dispatch('notify', ['type' => 'success',
                'message' => "{$this->run->run_number} approved. Journal entry posted. Ready for payment."]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function cancel(CancelPayrollRunAction $action): void
    {
        $this->validate(['cancelReason' => 'required|string|min:5']);

        try {
            $action->execute($this->run, $this->cancelReason, Auth::user());
            $this->showCancelModal = false;
            $this->run->refresh();
            $this->dispatch('notify', ['type' => 'warning', 'message' => "Run cancelled."]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function reverse(ReversePayrollRunAction $action): void
    {
        $this->validate(['reversalReason' => 'required|string|min:10']);

        try {
            $action->execute($this->run, $this->reversalReason, Auth::user());
            $this->showReverseModal = false;
            $this->run->refresh();
            $this->dispatch('notify', ['type' => 'success', 'message' => "Run reversed. Reversal journal posted."]);
        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.payroll.payroll-run-detail');
    }
}