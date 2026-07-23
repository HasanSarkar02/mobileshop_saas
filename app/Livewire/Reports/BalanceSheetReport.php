<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Balance Sheet')]
class BalanceSheetReport extends Component
{
    use HasReportFilter;
    use \App\Traits\HasAuthorization;

    public function mount(): void
{
    $this->requirePermission('accounting.view_full_reports');
}

    #[Computed]
    public function balanceSheet(): array
    {
        $shopId = Auth::user()->shop_id;
        $asOf   = $this->buildFilter()->dateRange->to->toDateString();

        $rows = DB::table('accounts')
            ->leftJoin('journal_entry_lines', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->leftJoin('journal_entries', function ($j) use ($asOf) {
                $j->on('journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                  ->where('journal_entries.entry_date', '<=', $asOf);
            })
            ->where('accounts.shop_id', $shopId)
            ->where('accounts.is_active', true)
            ->where('accounts.is_header', false)
            ->whereIn('accounts.type', ['asset', 'liability', 'equity'])
            ->selectRaw('
                accounts.code, accounts.name, accounts.type,
                COALESCE(SUM(journal_entry_lines.debit), 0)  AS dr,
                COALESCE(SUM(journal_entry_lines.credit), 0) AS cr
            ')
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get();

        $assets = $liabilities = $equity = [];
        $totalAssets = $totalLiabilities = $totalEquity = 0.0;

        // Retained earnings from P&L accounts
        $plResult = DB::table('accounts')
            ->join('journal_entry_lines', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->join('journal_entries', function ($j) use ($asOf) {
                $j->on('journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                  ->where('journal_entries.entry_date', '<=', $asOf);
            })
            ->where('accounts.shop_id', $shopId)
            ->whereIn('accounts.type', ['revenue', 'expense'])
            ->selectRaw('
                accounts.type,
                COALESCE(SUM(journal_entry_lines.debit), 0)  AS dr,
                COALESCE(SUM(journal_entry_lines.credit), 0) AS cr
            ')
            ->groupBy('accounts.type')
            ->get()
            ->keyBy('type');

        $totalRevenue  = (float)($plResult['revenue']?->cr ?? 0) - (float)($plResult['revenue']?->dr ?? 0);
        $totalExpenses = (float)($plResult['expense']?->dr ?? 0) - (float)($plResult['expense']?->cr ?? 0);
        $retainedEarnings = $totalRevenue - $totalExpenses;

        foreach ($rows as $row) {
            $dr = (float) $row->dr;
            $cr = (float) $row->cr;

            $balance = match($row->type) {
                'asset'             => $dr - $cr,
                'liability','equity'=> $cr - $dr,
            };

            if ($balance == 0) continue;

            match($row->type) {
                'asset'     => [$assets[]      = ['code' => $row->code, 'name' => $row->name, 'balance' => $balance], $totalAssets      += $balance],
                'liability' => [$liabilities[] = ['code' => $row->code, 'name' => $row->name, 'balance' => $balance], $totalLiabilities += $balance],
                'equity'    => [$equity[]      = ['code' => $row->code, 'name' => $row->name, 'balance' => $balance], $totalEquity      += $balance],
            };
        }

        if ($retainedEarnings != 0) {
            $equity[]     = ['code' => '—', 'name' => 'Retained Earnings (Current Year)', 'balance' => $retainedEarnings];
            $totalEquity += $retainedEarnings;
        }

        return compact(
            'assets', 'liabilities', 'equity',
            'totalAssets', 'totalLiabilities', 'totalEquity',
            'retainedEarnings', 'asOf'
        );
    }

    public function render()
    {
        return view('livewire.reports.balance-sheet-report', [
            'periodLabel' => 'As of ' . $this->buildFilter()->dateRange->to->format('d M Y'),
        ]);
    }
}