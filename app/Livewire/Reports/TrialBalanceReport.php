<?php

namespace App\Livewire\Reports;

use App\Livewire\Reports\Concerns\HasReportFilter;
use App\Reporting\Repositories\FinancialRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Trial Balance')]
class TrialBalanceReport extends Component
{
    use HasReportFilter;
    use \App\Traits\HasAuthorization;

    public function mount(): void
{
    $this->requirePermission('reports.financial');
}
    #[Computed]
    public function trialBalance(): array
    {
        $shopId = Auth::user()->shop_id;
        $filter = $this->buildFilter();

        $rows = DB::table('accounts')
            ->leftJoin('journal_entry_lines', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->leftJoin('journal_entries', function ($j) use ($filter) {
                $j->on('journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                  ->where('journal_entries.entry_date', '<=', $filter->dateRange->to->toDateString());
            })
            ->where('accounts.shop_id', $shopId)
            ->where('accounts.is_active', true)
            ->where('accounts.is_header', false)
            ->selectRaw('
                accounts.code,
                accounts.name,
                accounts.type,
                COALESCE(SUM(journal_entry_lines.debit), 0)  AS total_debit,
                COALESCE(SUM(journal_entry_lines.credit), 0) AS total_credit
            ')
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name', 'accounts.type')
            ->orderBy('accounts.code')
            ->get();

        $result = [];
        $totalDr = 0;
        $totalCr = 0;

        foreach ($rows as $row) {
            $dr = (float) $row->total_debit;
            $cr = (float) $row->total_credit;

            // Normal balance based on account type
            $balance = match($row->type) {
                'asset', 'expense' => $dr - $cr,   // debit normal
                default             => $cr - $dr,   // credit normal
            };

            if ($balance == 0 && $dr == 0 && $cr == 0) continue;

            $isDebitNormal = in_array($row->type, ['asset', 'expense']);

            if ($isDebitNormal) {
                // Asset & Expense
                $debitBalance  = max(0, $balance);
                $creditBalance = max(0, -$balance);
            } else {
                // Liability, Equity & Revenue
                $creditBalance = max(0, $balance);
                $debitBalance  = max(0, -$balance);
            }

            $totalDr += $debitBalance;
            $totalCr += $creditBalance;

            $result[] = [
                'code'           => $row->code,
                'name'           => $row->name,
                'type'           => $row->type,
                'debit_balance'  => $debitBalance,
                'credit_balance' => $creditBalance,
            ];

        }

        return [
            'rows'     => $result,
            'total_dr' => $totalDr,
            'total_cr' => $totalCr,
            'balanced' => abs($totalDr - $totalCr) < 0.01,
        ];
    }

    public function render()
    {
        return view('livewire.reports.trial-balance-report', [
            'periodLabel' => $this->periodLabel(),
        ]);
    }
}