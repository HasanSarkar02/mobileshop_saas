<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use App\Models\PaymentAccount;
use App\Reporting\DTOs\DateRange;
use App\Reporting\Repositories\FinancialRepository;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Account Statement')]
class AccountStatementReport extends Component
{
    use HasReportFilter;

    #[Url(as: 'account')]
    public int $accountId = 0;

    #[Computed]
    public function paymentAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        return PaymentAccount::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->orderBy('provider')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function statement(): ?object
    {
        if (! $this->accountId) return null;

        $filter = $this->buildFilter();

        return app(FinancialRepository::class)->accountStatement(
            Auth::user()->shop_id,
            $this->accountId,
            $filter->dateRange,
        );
    }

    public function render()
    {
        return view('livewire.reports.account-statement-report', [
            'branches'    => $this->getBranchesProperty(),
            'periodLabel' => $this->periodLabel(),
        ]);
    }
}