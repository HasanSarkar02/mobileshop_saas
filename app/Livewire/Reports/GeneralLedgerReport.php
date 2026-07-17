<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use App\Models\Account;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('General Ledger')]
class GeneralLedgerReport extends Component
{
    use HasReportFilter;
    use \App\Traits\HasAuthorization;

    #[Url(as: 'account')]
    public int    $accountId = 0;

    #[Url(as: 'type')]
    public string $typeFilter = '';

    public function mount(): void
{
    $this->requirePermission('reports.financial');
}

    #[Computed]
    public function accounts(): \Illuminate\Database\Eloquent\Collection
    {
        return Account::where('shop_id', Auth::user()->shop_id)
            ->where('is_active', true)
            ->where('is_header', false)
            ->when($this->typeFilter, fn ($q) => $q->where('type', $this->typeFilter))
            ->orderBy('code')
            ->get();
    }

    #[Computed]
    public function ledger(): ?object
    {
        if (! $this->accountId) return null;

        $filter  = $this->buildFilter();
        $shopId  = Auth::user()->shop_id;

        $account = Account::where('shop_id', $shopId)->findOrFail($this->accountId);

        $opening = (float) DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $this->accountId)
            ->where('journal_entries.shop_id', $shopId)
            ->where('journal_entries.entry_date', '<', $filter->dateRange->from->toDateString())
            ->selectRaw('COALESCE(SUM(debit),0) - COALESCE(SUM(credit),0) AS balance')
            ->first()?->balance ?? 0;

        $lines = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $this->accountId)
            ->where('journal_entries.shop_id', $shopId)
            ->whereBetween('journal_entries.entry_date', [
                $filter->dateRange->from->toDateString(),
                $filter->dateRange->to->toDateString(),
            ])
            ->selectRaw('
                journal_entries.entry_date,
                journal_entries.entry_number,
                journal_entries.description,
                journal_entries.reference_type,
                journal_entries.reference_id,
                journal_entry_lines.description AS line_desc,
                COALESCE(journal_entry_lines.debit, 0)  AS debit,
                COALESCE(journal_entry_lines.credit, 0) AS credit
            ')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->get();

        $running = $opening;
        $lines   = $lines->map(function ($line) use (&$running) {
            $running += (float)$line->debit - (float)$line->credit;
            $line->balance = $running;
            return $line;
        });

        return (object) [
            'account'  => $account,
            'opening'  => $opening,
            'lines'    => $lines,
            'closing'  => $running,
            'total_dr' => (float) $lines->sum('debit'),
            'total_cr' => (float) $lines->sum('credit'),
        ];
    }

    public function render()
    {
        return view('livewire.reports.general-ledger-report', [
            'periodLabel' => $this->periodLabel(),
        ]);
    }
}