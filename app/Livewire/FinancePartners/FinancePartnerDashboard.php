<?php

namespace App\Livewire\FinancePartners;

use App\Enums\FPReceivableStatus;
use App\Models\FinancePartner;
use App\Models\FinancePartnerReceivable;
use App\Models\FinancePartnerSettlement;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Finance Partners')]
class FinancePartnerDashboard extends Component
{
    use WithPagination;

    #[Url]
    public int $selectedPartnerId = 0;

    #[Url]
    public string $activeTab = 'receivables';

    #[Computed]
    public function partners(): \Illuminate\Database\Eloquent\Collection
    {
        return FinancePartner::where('is_active', true)->orderBy('name')->get();
    }

    #[Url]
    public string $receivableFilter = 'pending'; // 'pending' | 'all'

    #[Computed]
    public function partnerSummaries(): array
    {
        $shopId = Auth::user()->shop_id;

        return FinancePartner::where('is_active', true)
            ->get()
            ->mapWithKeys(fn ($partner) => [
                $partner->id => [
                    'name' => $partner->name,
                    // Only count genuinely owed amounts — never cancelled/written_off
                    'total_pending' => (float) FinancePartnerReceivable::withoutGlobalScopes()
                        ->where('shop_id', $shopId)
                        ->where('finance_partner_id', $partner->id)
                        ->whereIn('status', [
                            FPReceivableStatus::Pending->value,
                            FPReceivableStatus::Partial->value,
                        ])
                        ->sum(\Illuminate\Support\Facades\DB::raw('total_amount - settled_amount')),

                    'pending_count' => FinancePartnerReceivable::withoutGlobalScopes()
                        ->where('shop_id', $shopId)
                        ->where('finance_partner_id', $partner->id)
                        ->whereIn('status', [
                            FPReceivableStatus::Pending->value,
                            FPReceivableStatus::Partial->value,
                        ])
                        ->count(),

                    'total_settled' => (float) FinancePartnerReceivable::withoutGlobalScopes()
                        ->where('shop_id', $shopId)
                        ->where('finance_partner_id', $partner->id)
                        ->where('status', FPReceivableStatus::Settled->value)
                        ->sum('total_amount'),
                ],
            ])
            ->toArray();
    }

    public function render()
    {
        $partner = $this->selectedPartnerId
            ? FinancePartner::findOrFail($this->selectedPartnerId)
            : null;

        $receivables = null;
        $settlements = null;

        if ($partner) {
            if ($this->activeTab === 'receivables') {
            $receivables = FinancePartnerReceivable::with('sale.customer')
                ->where('finance_partner_id', $partner->id)
                ->when(
                    $this->receivableFilter === 'pending',
                    fn ($q) => $q->whereIn('status', [
                        FPReceivableStatus::Pending->value,
                        FPReceivableStatus::Partial->value,
                    ])
                )
                ->latest()
                ->paginate(20, pageName: 'rPage');
        }

            if ($this->activeTab === 'settlements') {
                $settlements = FinancePartnerSettlement::with(['paymentAccount', 'allocations'])
                    ->where('finance_partner_id', $partner->id)
                    ->latest('settlement_date')
                    ->paginate(15, pageName: 'sPage');
            }
        }

        return view('livewire.finance-partners.finance-partner-dashboard',
            compact('partner', 'receivables', 'settlements'));
    }
}