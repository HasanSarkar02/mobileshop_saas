<?php

namespace App\Livewire\Payroll;

use App\Actions\ProcessPayrollAction;
use App\Enums\PayrollStatus;
use App\Models\PaymentAccount;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Manage Payroll')]
class ManagePayroll extends Component
{
    public PayrollRun $run;

    // Editable fields per item (indexed by payroll_item id)
    public array $bonuses     = [];
    public array $deductions  = [];
    public array $notes       = [];
    public array $accountIds  = [];

    public function mount(PayrollRun $run): void
    {
        if ($run->status === PayrollStatus::Paid) {
            $this->redirect(route('payroll.index'), navigate: true);
            return;
        }

        $this->run = $run->load('items.user.employeeProfile', 'items.paymentAccount');

        foreach ($run->items as $item) {
            $this->bonuses[$item->id]    = (string) $item->bonus;
            $this->deductions[$item->id] = (string) $item->other_deduction;
            $this->notes[$item->id]      = $item->notes ?? '';
            $this->accountIds[$item->id] = $item->payment_account_id ?? 0;
        }
    }

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('is_active', true)->get();
    }

    public function updateItem(int $itemId): void
    {
        $item  = $this->run->items->firstWhere('id', $itemId);
        if (! $item) return;

        $oldBonus  = (float) $item->bonus;
        $oldDed    = (float) $item->other_deduction;
        $oldNet    = (float) $item->net_salary;

        $bonus     = (float) ($this->bonuses[$itemId] ?? 0);
        $otherDed  = (float) ($this->deductions[$itemId] ?? 0);
        $totalDed  = (float) $item->advance_deduction + $otherDed;
        $grossPlusBonus = (float) $item->gross_salary + $bonus;
        $net       = max(0, $grossPlusBonus - $totalDed);

        $item->update([
            'bonus'           => $bonus,
            'other_deduction' => $otherDed,
            'total_deductions'=> $totalDed,
            'net_salary'      => $net,
            'notes'           => $this->notes[$itemId] ?? null,
            'payment_account_id' => $this->accountIds[$itemId] ?: null,
        ]);

        activity()
            ->causedBy(Auth::user())
            ->performedOn($item)
            ->withProperties([
                'employee_id'    => $item->user_id,
                'employee_name'  => $item->user?->name,
                'payroll_run_id' => $this->run->id,
                'bonus'          => $bonus,
                'old_bonus'      => $oldBonus,
                'other_deduction'=> $otherDed,
                'old_deduction'  => $oldDed,
                'net_salary'     => $net,
                'old_net_salary' => $oldNet,
            ])
            ->log('payroll.item_updated');

        app(ProcessPayrollAction::class)->recalculateTotals($this->run);
        $this->run->refresh()->load('items.user');
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Item updated.']);
    }

    public function render()
    {
        return view('livewire.payroll.manage-payroll', ['run' => $this->run]);
    }
}