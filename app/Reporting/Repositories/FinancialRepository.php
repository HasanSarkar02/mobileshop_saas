<?php

namespace App\Reporting\Repositories;

use App\Reporting\DTOs\ReportFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialRepository extends BaseReportRepository
{
    /** GL account balances for P&L and Balance Sheet — core financial query */
    public function accountBalances(int $shopId, ReportFilter $filter): Collection
    {
        return DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.shop_id', $shopId)
            ->whereBetween('journal_entries.entry_date', [
                $filter->dateRange->from->toDateString(),
                $filter->dateRange->to->toDateString(),
            ])
            ->when($filter->branchId, fn ($q) => $q->where('journal_entries.branch_id', $filter->branchId))
            ->selectRaw('
                accounts.code,
                accounts.name AS account_name,
                accounts.type,
                accounts.subtype,
                COALESCE(SUM(journal_entry_lines.debit), 0)  AS total_debit,
                COALESCE(SUM(journal_entry_lines.credit), 0) AS total_credit,
                COALESCE(SUM(journal_entry_lines.debit), 0) - COALESCE(SUM(journal_entry_lines.credit), 0) AS net_balance
            ')
            ->groupBy('accounts.id', 'accounts.code', 'accounts.name',
                      'accounts.type', 'accounts.subtype')
            ->orderBy('accounts.code')
            ->get();
    }

    /** Cash position per payment account (current real-time balance) */
    public function cashPositionByAccount(int $shopId): Collection
    {
        return DB::table('payment_accounts')
            ->join('accounts', 'accounts.id', '=', 'payment_accounts.account_id')
            ->leftJoin('journal_entry_lines', 'journal_entry_lines.account_id', '=', 'accounts.id')
            ->leftJoin('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('payment_accounts.shop_id', $shopId)
            ->where('payment_accounts.is_active', true)
            ->selectRaw('
                payment_accounts.id,
                payment_accounts.name,
                payment_accounts.provider,
                accounts.code,
                COALESCE(SUM(journal_entry_lines.debit), 0) - COALESCE(SUM(journal_entry_lines.credit), 0) AS balance
            ')
            ->groupBy('payment_accounts.id', 'payment_accounts.name',
                      'payment_accounts.provider', 'accounts.code')
            ->orderBy('payment_accounts.provider')
            ->get();
    }

    /** Supplier payables outstanding */
    public function supplierPayables(int $shopId): object
    {
        $account = DB::table('accounts')
            ->where('shop_id', $shopId)->where('code', '2000')->first();

        if (! $account) return (object) ['total' => 0];

        $balance = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.shop_id', $shopId)
            ->where('journal_entry_lines.account_id', $account->id)
            ->selectRaw('
                COALESCE(SUM(credit), 0) - COALESCE(SUM(debit), 0) AS payable
            ')
            ->first();

        return (object) ['total' => (float) ($balance->payable ?? 0)];
    }

    /** Journal entries for general ledger view */
    public function journalEntries(ReportFilter $filter, int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return DB::table('journal_entries')
            ->where('shop_id', $filter->shopId)
            ->whereBetween('entry_date', [
                $filter->dateRange->from->toDateString(),
                $filter->dateRange->to->toDateString(),
            ])
            ->when($filter->branchId, fn ($q) => $q->where('branch_id', $filter->branchId))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /** Expense breakdown by GL account for P&L */
    public function expensesByGlAccount(ReportFilter $filter): Collection
    {
        return DB::table('expenses')
            ->join('expense_categories', 'expense_categories.id', '=', 'expenses.expense_category_id')
            ->where('expenses.shop_id', $filter->shopId)
            ->where('expenses.status', 'approved')
            ->whereBetween('expenses.expense_date', [
                $filter->dateRange->from->toDateString(),
                $filter->dateRange->to->toDateString(),
            ])
            ->when($filter->branchId, fn ($q) => $q->where('expenses.branch_id', $filter->branchId))
            ->selectRaw('
                COALESCE(expense_categories.gl_account_code, "6030") AS gl_code,
                expense_categories.name AS category_name,
                COUNT(*) AS entry_count,
                COALESCE(SUM(expenses.amount), 0) AS total_amount
            ')
            ->groupBy('expense_categories.id', 'expense_categories.name',
                      'expense_categories.gl_account_code')
            ->orderByRaw('total_amount DESC')
            ->get();
    }
}