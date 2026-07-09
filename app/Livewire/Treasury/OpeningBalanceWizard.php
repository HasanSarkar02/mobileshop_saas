<?php

namespace App\Livewire\Treasury;

use App\Actions\Treasury\CreateTreasuryTransactionAction;
use App\Enums\TreasuryTransactionStatus;
use App\Models\Branch;
use App\Models\PaymentAccount;
use App\Models\TreasuryTransaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Opening Balance Setup')]
class OpeningBalanceWizard extends Component
{
    use \App\Traits\HasAuthorization;

    // Balances indexed by payment_account_id
    public array  $balances    = [];
    public string $asOfDate    = '';
    public int    $branchId    = 0;
    public array  $saved       = [];   // account IDs already saved this session

    public function mount(): void
    {
        $this->requirePermission('treasury.create_equity');

        $this->asOfDate = now()->subDay()->format('Y-m-d');
        $this->branchId = (int) (
            Auth::user()->branch_id
            ?? Branch::where('shop_id', Auth::user()->shop_id)->where('is_main', true)->value('id')
            ?? 0
        );

        // Pre-fill zero for each account
        foreach ($this->allAccounts as $acc) {
            $this->balances[$acc->id] = '';
        }
    }

    #[Computed]
    public function allAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->orderBy('provider')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function existingOpeningBalances(): array
    {
        return TreasuryTransaction::where('shop_id', Auth::user()->shop_id)
            ->where('transaction_type', 'opening_balance')
            ->whereNotIn('status', ['rejected', 'reversed'])
            ->pluck('to_payment_account_id')
            ->toArray();
    }

    public function saveAccount(int $accountId, CreateTreasuryTransactionAction $action): void
    {
        $this->validate([
            "balances.{$accountId}" => 'required|numeric|min:0',
            'asOfDate'              => 'required|date',
        ], [
            "balances.{$accountId}.required" => 'Please enter a balance amount.',
            "balances.{$accountId}.min"      => 'Balance must be zero or positive.',
        ]);

        $balance = (float) $this->balances[$accountId];

        if ($balance <= 0) {
            $this->dispatch('notify', ['type' => 'error',
                'message' => 'Opening balance must be greater than zero. Skip this account if it has no balance.']);
            return;
        }

        $shop = Auth::user()->shop()->withoutGlobalScopes()->findOrFail(Auth::user()->shop_id);
        $acc  = PaymentAccount::findOrFail($accountId);

        try {
            $action->execute($shop, [
                'transaction_type'       => 'opening_balance',
                'branch_id'              => $this->branchId,
                'to_payment_account_id'  => $accountId,
                'amount'                 => $balance,
                'fee_amount'             => 0,
                'transaction_date'       => $this->asOfDate,
                'description'            => "Opening balance — {$acc->name} as of {$this->asOfDate}",
                'notes'                  => 'Set via Opening Balance Wizard',
            ], Auth::user());

            $this->saved[] = $accountId;
            unset($this->existingOpeningBalances);

            $this->dispatch('notify', ['type' => 'success',
                'message' => "Opening balance of ৳{$balance} set for {$acc->name}."]);

        } catch (\Exception $e) {
            $this->dispatch('notify', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.treasury.opening-balance-wizard');
    }
}