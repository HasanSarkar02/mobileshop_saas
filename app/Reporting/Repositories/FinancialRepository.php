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

    /**
     * Account Statement — all movements for ONE payment account in a period.
     * Returns opening balance, all journal lines with running balance, closing balance.
     * Used for: Bank statements, MFS account reconciliation, audit requests.
     */
    public function accountStatement(
        int       $shopId,
        int       $paymentAccountId,
        \App\Reporting\DTOs\DateRange $dateRange
    ): object {
        $pa = \App\Models\PaymentAccount::withoutGlobalScopes()
            ->findOrFail($paymentAccountId);

        // Opening balance = everything BEFORE period start
        $opening = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $pa->account_id)
            ->where('journal_entries.shop_id', $shopId)
            ->where('journal_entries.entry_date', '<', $dateRange->from->toDateString())
            ->selectRaw('
                COALESCE(SUM(journal_entry_lines.debit), 0) -
                COALESCE(SUM(journal_entry_lines.credit), 0) AS balance
            ')
            ->first();

        $openingBalance = (float) ($opening->balance ?? 0);

        // All lines in period for this account
        $lines = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $pa->account_id)
            ->where('journal_entries.shop_id', $shopId)
            ->whereBetween('journal_entries.entry_date', [
                $dateRange->from->toDateString(),
                $dateRange->to->toDateString(),
            ])
            ->selectRaw('
                journal_entries.entry_date,
                journal_entries.entry_number,
                journal_entries.description AS entry_description,
                journal_entries.reference_type,
                journal_entries.reference_id,
                journal_entry_lines.description AS line_description,
                COALESCE(journal_entry_lines.debit, 0)  AS debit,
                COALESCE(journal_entry_lines.credit, 0) AS credit
            ')
            ->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.id')
            ->get();

        // Attach running balance to each line
        $running = $openingBalance;
        $lines   = $lines->map(function ($line) use (&$running) {
            $running += (float) $line->debit - (float) $line->credit;
            $line->running_balance = $running;
            return $line;
        });

        return (object) [
            'account'         => $pa,
            'opening_balance' => $openingBalance,
            'lines'           => $lines,
            'period_debits'   => (float) $lines->sum('debit'),
            'period_credits'  => (float) $lines->sum('credit'),
            'closing_balance' => $running,
            'date_range'      => $dateRange,
        ];
    }

    /**
     * Cash Flow data — direct method.
     * Classifies all cash movements from journal_entry_lines by reference_type.
     * Financing activities pulled directly from treasury_transactions.
     */
    public function cashFlow(int $shopId, \App\Reporting\DTOs\ReportFilter $filter): array
    {
        // All payment account GL IDs for this shop
        $payGlIds = DB::table('payment_accounts')
            ->join('accounts', 'accounts.id', '=', 'payment_accounts.account_id')
            ->where('payment_accounts.shop_id', $shopId)
            ->when($filter->branchId, fn ($q) => $q->where('payment_accounts.branch_id', $filter->branchId))
            ->pluck('accounts.id');

        if ($payGlIds->isEmpty()) {
            return $this->emptyCashFlow($shopId, $filter);
        }

        // All cash movements in period
        $movements = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereIn('journal_entry_lines.account_id', $payGlIds)
            ->where('journal_entries.shop_id', $shopId)
            ->whereBetween('journal_entries.entry_date', [
                $filter->dateRange->from->toDateString(),
                $filter->dateRange->to->toDateString(),
            ])
            ->selectRaw('
                journal_entries.reference_type,
                COALESCE(journal_entry_lines.debit, 0)  AS debit,
                COALESCE(journal_entry_lines.credit, 0) AS credit
            ')
            ->get();

        // Classify by reference_type
        $byRef = fn (string $class, string $col) =>
            (float) $movements->where('reference_type', $class)->sum($col);

        $saleClass     = \App\Models\Sale::class;
        $cnClass       = \App\Models\CreditNote::class;
        $purchaseClass = \App\Models\Purchase::class;
        $expenseClass  = \App\Models\Expense::class;
        $drawClass     = \App\Models\SalaryDraw::class;
        $payrollClass  = \App\Models\PayrollRun::class;
        $serviceClass  = \App\Models\ServicePayment::class;

        $salesReceipts   = $byRef($saleClass,    'debit');
        $salesReturns    = $byRef($cnClass,       'credit');
        $serviceReceipts = $byRef($serviceClass,  'debit');
        $supplierPaid    = $byRef($purchaseClass, 'credit');
        $expensePaid     = $byRef($expenseClass,  'credit');
        $salaryPaid      = (float) $movements
            ->whereIn('reference_type', [$drawClass, $payrollClass])
            ->sum('credit');

        $netOperating = $salesReceipts - $salesReturns + $serviceReceipts
                      - $supplierPaid - $expensePaid - $salaryPaid;

        // Financing activities from treasury_transactions (already classified)
        $financing = DB::table('treasury_transactions')
            ->where('shop_id', $shopId)
            ->where('status', 'completed')
            ->whereBetween('transaction_date', [
                $filter->dateRange->from->toDateString(),
                $filter->dateRange->to->toDateString(),
            ])
            ->whereIn('transaction_type', [
                'owner_capital', 'owner_drawings',
                'partner_investment', 'partner_withdrawal',
                'loan_receipt', 'loan_repayment',
            ])
            ->when($filter->branchId, fn ($q) => $q->where('branch_id', $filter->branchId))
            ->selectRaw('transaction_type, SUM(amount) AS total, SUM(fee_amount) AS total_fee')
            ->groupBy('transaction_type')
            ->get()
            ->keyBy('transaction_type');

        $fn = fn (string $key) => (float) ($financing[$key]?->total ?? 0);

        $capitalIn    = $fn('owner_capital')    + $fn('partner_investment');
        $capitalOut   = $fn('owner_drawings')   + $fn('partner_withdrawal');
        $loansIn      = $fn('loan_receipt');
        $loansOut     = $fn('loan_repayment')   + (float) ($financing['loan_repayment']?->total_fee ?? 0);
        $netFinancing = $capitalIn - $capitalOut + $loansIn - $loansOut;

        // Opening balance (all payment accounts BEFORE period)
        $openingBalance = (float) DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->whereIn('journal_entry_lines.account_id', $payGlIds)
            ->where('journal_entries.shop_id', $shopId)
            ->where('journal_entries.entry_date', '<', $filter->dateRange->from->toDateString())
            ->selectRaw('COALESCE(SUM(debit), 0) - COALESCE(SUM(credit), 0) AS balance')
            ->first()
            ?->balance ?? 0;

        return [
            // Operating
            'sales_receipts'    => $salesReceipts,
            'sales_returns'     => $salesReturns,
            'service_receipts'  => $serviceReceipts,
            'supplier_paid'     => $supplierPaid,
            'expense_paid'      => $expensePaid,
            'salary_paid'       => $salaryPaid,
            'net_operating'     => $netOperating,

            // Financing
            'capital_in'        => $capitalIn,
            'capital_out'       => $capitalOut,
            'loans_in'          => $loansIn,
            'loans_out'         => $loansOut,
            'net_financing'     => $netFinancing,

            // Net
            'net_change'        => $netOperating + $netFinancing,
            'opening_balance'   => $openingBalance,
            'closing_balance'   => $openingBalance + $netOperating + $netFinancing,
            'period_label'      => $filter->dateRange->toDisplayString(),
        ];
    }

    private function emptyCashFlow(int $shopId, \App\Reporting\DTOs\ReportFilter $filter): array
    {
        return array_fill_keys([
            'sales_receipts','sales_returns','service_receipts','supplier_paid',
            'expense_paid','salary_paid','net_operating','capital_in','capital_out',
            'loans_in','loans_out','net_financing','net_change','opening_balance',
            'closing_balance',
        ], 0.0) + ['period_label' => $filter->dateRange->toDisplayString()];
    }
}