<?php

namespace App\Reporting\Services;

use App\Reporting\DTOs\ProfitLossDTO;
use App\Reporting\DTOs\ReportFilter;
use App\Reporting\Repositories\FinancialRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    public function __construct(private readonly FinancialRepository $repo) {}

    /**
     * Profit & Loss Statement — built entirely from GL journal entries.
     * This is THE authoritative P&L — not a re-sum of sales/expense tables.
     * Consistent with the double-entry accounting engine.
     */
    public function profitAndLoss(ReportFilter $filter, bool $withPrevious = true): ProfitLossDTO
    {
        $balances = $this->repo->accountBalances($filter->shopId, $filter);

        // Group by account code prefix for P&L structure
        $byCode = $balances->keyBy('code');

        $salesRevenue   = (float) abs($byCode->get('4000')?->net_balance ?? 0);
        $serviceRevenue = (float) abs($byCode->get('4030')?->net_balance ?? 0);
        $salesReturns   = (float) ($byCode->get('4010')?->net_balance ?? 0);  // debit = positive
        $salesDiscounts = (float) ($byCode->get('4020')?->net_balance ?? 0);

        $netRevenue = ($salesRevenue + $serviceRevenue) - $salesReturns - $salesDiscounts;

        $cogs           = (float) ($byCode->get('5000')?->net_balance ?? 0);
        $serviceParts   = (float) ($byCode->get('5020')?->net_balance ?? 0);
        $grossProfit    = $netRevenue - $cogs - $serviceParts;
        $grossMargin    = $netRevenue > 0 ? round($grossProfit / $netRevenue * 100, 2) : 0;

        // Expenses (all 6xxx accounts)
        $expenses = $balances->filter(fn ($a) => str_starts_with((string)$a->code, '6'));
        $expensesByAccount = $expenses->mapWithKeys(fn ($a) => [
            $a->account_name => (float) ($a->net_balance ?? 0)
        ])->toArray();
        $totalExpenses = (float) $expenses->sum('net_balance');

        $operatingProfit = $grossProfit - $totalExpenses;
        $opMargin        = $netRevenue > 0 ? round($operatingProfit / $netRevenue * 100, 2) : 0;
        $netMargin       = $netRevenue > 0 ? round($operatingProfit / $netRevenue * 100, 2) : 0;

        $prevDTO = null;
        if ($withPrevious) {
            $prevDTO = $this->profitAndLoss($filter->forPreviousPeriod(), false);
        }

        return new ProfitLossDTO(
            salesRevenue:           $salesRevenue,
            serviceRevenue:         $serviceRevenue,
            salesReturns:           $salesReturns,
            salesDiscounts:         $salesDiscounts,
            netRevenue:             $netRevenue,
            costOfGoodsSold:        $cogs,
            costOfServiceParts:     $serviceParts,
            grossProfit:            $grossProfit,
            grossMarginPct:         $grossMargin,
            expensesByAccount:      $expensesByAccount,
            totalOperatingExpenses: $totalExpenses,
            operatingProfit:        $operatingProfit,
            operatingMarginPct:     $opMargin,
            otherIncome:            0,
            otherExpenses:          0,
            netProfit:              $operatingProfit,
            netMarginPct:           $netMargin,
            periodLabel:            $filter->dateRange->toDisplayString(),
            previousPeriod:         $prevDTO,
        );
    }

    public function cashPositionByAccount(int $shopId): Collection
    {
        return $this->repo->cashPositionByAccount($shopId);
    }

    public function journalEntries(ReportFilter $filter, int $perPage = 50)
    {
        return $this->repo->journalEntries($filter, $perPage);
    }

    public function expensesByCategory(ReportFilter $filter): Collection
    {
        return $this->repo->expensesByGlAccount($filter);
    }
}