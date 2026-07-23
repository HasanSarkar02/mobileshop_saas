<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\PayrollRun;
use App\Models\Purchase;
use App\Models\ProductUnit;
use App\Models\Sale;
use App\Models\ServiceTicket;
use App\Models\UsedPhoneAcquisition;
use App\Reporting\DTOs\ReportFilter;
use App\Reporting\Enums\ReportPeriod;
use App\Reporting\Services\FinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocumentController extends Controller
{
    private function shopGuard(int $shopId): void
    {
        $user = Auth::user();
        if ($user->shop_id !== $shopId && ! $user->isSuperAdmin()) {
            abort(403);
        }
    }

    // ── Sale Invoice ──────────────────────────────────────────────────────────

    // ── Sale Invoice ──────────────────────────────────────────────────────────

    public function saleInvoice(Sale $sale)
    {
        $this->shopGuard($sale->shop_id);
        $sale->load([
            'shop', 'branch', 'cashier', 'customer',
            'items', 'payments.paymentAccount',
        ]);

        $presenter = new \App\Presenters\SaleInvoicePresenter($sale);

        return view('documents.sale-invoice', compact('sale', 'presenter'));
    }

    public function saleInvoicePdf(Sale $sale)
    {
        $this->shopGuard($sale->shop_id);
        $sale->load([
            'shop', 'branch', 'cashier', 'customer',
            'items', 'payments.paymentAccount',
        ]);

        $presenter = new \App\Presenters\SaleInvoicePresenter($sale);

        $pdf = Pdf::loadView('documents.sale-invoice', compact('sale', 'presenter'))
            ->setPaper('A4', 'portrait');

        return $pdf->download("{$sale->sale_number}.pdf");
    }

    // ── Credit Note ───────────────────────────────────────────────────────────

    public function creditNote(CreditNote $creditNote)
    {
        $this->shopGuard($creditNote->shop_id);
        $creditNote->load(['shop', 'branch', 'customer', 'originalSale', 'items', 'createdBy']);
        return view('documents.credit-note', compact('creditNote'));
    }

    public function creditNotePdf(CreditNote $creditNote)
    {
        $this->shopGuard($creditNote->shop_id);
        $creditNote->load(['shop', 'branch', 'customer', 'originalSale', 'items', 'createdBy']);
        return Pdf::loadView('documents.credit-note', compact('creditNote'))
            ->setPaper('A4', 'portrait')
            ->download("{$creditNote->credit_note_number}.pdf");
    }

    // ── Purchase Invoice ──────────────────────────────────────────────────────

    public function purchaseInvoice(Purchase $purchase)
    {
        $this->shopGuard($purchase->shop_id);
        $purchase->load(['shop', 'branch', 'supplier', 'lineItems.variant.product', 'createdBy']);
        return view('documents.purchase-invoice', compact('purchase'));
    }

    public function purchaseInvoicePdf(Purchase $purchase)
    {
        $this->shopGuard($purchase->shop_id);
        $purchase->load(['shop', 'branch', 'supplier', 'lineItems.variant.product', 'createdBy']);
        return Pdf::loadView('documents.purchase-invoice', compact('purchase'))
            ->setPaper('A4', 'portrait')
            ->download("{$purchase->reference_number}.pdf");
    }

    // ── Service Invoice ───────────────────────────────────────────────────────

    public function serviceInvoice(ServiceTicket $ticket)
    {
        $this->shopGuard($ticket->shop_id);
        $ticket->load(['shop', 'branch', 'customer', 'technician', 'parts.variant.product', 'payments.paymentAccount', 'productUnit']);
        return view('documents.service-invoice', compact('ticket'));
    }

    public function serviceInvoicePdf(ServiceTicket $ticket)
    {
        $this->shopGuard($ticket->shop_id);
        $pdf = true;
        $ticket->load(['shop', 'branch', 'customer', 'technician', 'parts.variant.product', 'payments.paymentAccount', 'productUnit']);
        return Pdf::loadView('documents.service-invoice', compact('ticket', 'pdf'))
            ->setPaper('A4', 'portrait')
            ->download("{$ticket->ticket_number}.pdf");
    }

    // ── Warranty Slip ─────────────────────────────────────────────────────────

    public function warrantySlip(ProductUnit $unit)
    {
        $this->shopGuard($unit->shop_id);
        $unit->load(['variant.product.brand', 'branch']);

        // Find the sale record for this unit
        $sale = Sale::with(['customer'])->whereHas('items', fn ($q) =>
            $q->where('product_unit_id', $unit->id)
        )->first();

        return view('documents.warranty-slip', compact('unit', 'sale'));
    }

    // ── Payroll Sheet ─────────────────────────────────────────────────────────

    public function payrollSheet(PayrollRun $run)
    {
        $this->shopGuard($run->shop_id);
        $run->load(['shop', 'items.user.employeeProfile', 'createdBy', 'approvedBy']);
        return view('documents.payroll-sheet', compact('run'));
    }

    public function payrollSheetPdf(PayrollRun $run)
    {
        $this->shopGuard($run->shop_id);
        $run->load(['shop', 'items.user.employeeProfile', 'createdBy', 'approvedBy']);
        return Pdf::loadView('documents.payroll-sheet', compact('run'))
            ->setPaper('A4', $run->items->count() > 6 ? 'landscape' : 'portrait')
            ->download("payroll-{$run->year}-{$run->month}.pdf");
    }

    // ── Used Phone Receipt ────────────────────────────────────────────────────

    public function usedPhoneReceipt(UsedPhoneAcquisition $acquisition)
    {
        $this->shopGuard($acquisition->shop_id);
        $acquisition->load(['shop', 'branch', 'variant.product', 'paymentAccount', 'createdBy']);
        return view('documents.used-phone-receipt', compact('acquisition'));
    }

    public function usedPhoneReceiptPdf(UsedPhoneAcquisition $acquisition)
    {
        $this->shopGuard($acquisition->shop_id);
        $acquisition->load(['shop', 'branch', 'variant.product', 'paymentAccount', 'createdBy']);
        return Pdf::loadView('documents.used-phone-receipt', compact('acquisition'))
            ->setPaper('A4', 'portrait')
            ->download("{$acquisition->acquisition_number}.pdf");
    }

    // ── P&L Report Print ──────────────────────────────────────────────────────

    public function profitLossPrint(Request $request)
    {
        $shopId = Auth::user()->shop_id;

        $filter = $request->filled('from') && $request->filled('to')
            ? ReportFilter::forShopAndDateRange($shopId, $request->from, $request->to)
            : ReportFilter::forShop($shopId, ReportPeriod::from($request->get('period', 'this_month')));

        if ($request->branchId) {
            $filter = new ReportFilter(
                shopId: $shopId,
                dateRange: $filter->dateRange,
                branchId: (int) $request->branchId,
                period: $filter->period,
            );
        }

        $report      = app(FinancialReportService::class)->profitAndLoss($filter);
        $periodLabel = $filter->dateRange->toDisplayString();
        $branchId    = $request->branchId;

        return view('documents.report-profit-loss', compact('report', 'periodLabel', 'branchId'));
    }

    public function profitLossPdf(Request $request)
    {
        $shopId = Auth::user()->shop_id;
        $filter = $request->filled('from') && $request->filled('to')
            ? ReportFilter::forShopAndDateRange($shopId, $request->from, $request->to)
            : ReportFilter::forShop($shopId, ReportPeriod::from($request->get('period', 'this_month')));

        $report      = app(FinancialReportService::class)->profitAndLoss($filter);
        $periodLabel = $filter->dateRange->toDisplayString();
        $branchId    = $request->branchId ?? null;

        return Pdf::loadView('documents.report-profit-loss', compact('report', 'periodLabel', 'branchId'))
            ->setPaper('A4', 'portrait')
            ->download("profit-loss-{$periodLabel}.pdf");
    }

    // ── CSV Exports ───────────────────────────────────────────────────────────

    public function profitLossCsv(Request $request)
    {
        $shopId = Auth::user()->shop_id;
        $filter = ReportFilter::forShop($shopId,
            ReportPeriod::from($request->get('period', 'this_month')));

        $report = app(FinancialReportService::class)->profitAndLoss($filter);
        $period = $filter->dateRange->toDisplayString();

        $rows = [
            ['Profit & Loss Statement', $period],
            [''],
            ['REVENUE', ''],
            ['Sales Revenue',       number_format($report->salesRevenue, 2)],
            ['Service Revenue',     number_format($report->serviceRevenue, 2)],
            ['Sales Returns',       '(' . number_format($report->salesReturns, 2) . ')'],
            ['Sales Discounts',     '(' . number_format($report->salesDiscounts, 2) . ')'],
            ['Net Revenue',         number_format($report->netRevenue, 2)],
            [''],
            ['COST OF SALES', ''],
            ['Cost of Goods Sold',  number_format($report->costOfGoodsSold, 2)],
            ['Cost of Service Parts', number_format($report->costOfServiceParts, 2)],
            ['Gross Profit',        number_format($report->grossProfit, 2)],
            ['Gross Margin',        $report->grossMarginPct . '%'],
            [''],
            ['OPERATING EXPENSES', ''],
        ];

        foreach ($report->expensesByAccount as $name => $amount) {
            $rows[] = [$name, number_format($amount, 2)];
        }

        $rows = array_merge($rows, [
            ['Total Operating Expenses', number_format($report->totalOperatingExpenses, 2)],
            [''],
            ['NET PROFIT / LOSS', number_format($report->netProfit, 2)],
            ['Net Margin', $report->netMarginPct . '%'],
        ]);

        $filename = "pl-{$period}.csv";
        $handle   = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── Account Statement Print / PDF ─────────────────────────────────────────

    public function accountStatementPrint(Request $request)
    {
        $shopId     = Auth::user()->shop_id;
        $accountId  = (int) $request->account;
        $branchId   = $request->branchId ?? null;

        $account = \App\Models\PaymentAccount::findOrFail($accountId);
        if ($account->shop_id !== $shopId) abort(403);

        $filter    = $this->buildFilterFromRequest($request, $shopId);
        $statement = app(\App\Reporting\Repositories\FinancialRepository::class)
            ->accountStatement($shopId, $accountId, $filter->dateRange);

        $periodLabel = $filter->dateRange->toDisplayString();

        return view('documents.account-statement', compact(
            'statement', 'periodLabel', 'branchId'
        ));
    }

    public function accountStatementPdf(Request $request)
    {
        $shopId    = Auth::user()->shop_id;
        $accountId = (int) $request->account;

        $account = \App\Models\PaymentAccount::findOrFail($accountId);
        if ($account->shop_id !== $shopId) abort(403);

        $filter    = $this->buildFilterFromRequest($request, $shopId);
        $statement = app(\App\Reporting\Repositories\FinancialRepository::class)
            ->accountStatement($shopId, $accountId, $filter->dateRange);

        $periodLabel = $filter->dateRange->toDisplayString();
        $branchId    = $request->branchId ?? null;

        return Pdf::loadView('documents.account-statement',
            compact('statement', 'periodLabel', 'branchId'))
            ->setPaper('A4', 'portrait')
            ->download("account-statement-{$account->name}-{$periodLabel}.pdf");
    }

    // ── Cash Flow Print ───────────────────────────────────────────────────────

    public function cashFlowPrint(Request $request)
    {
        $shopId   = Auth::user()->shop_id;
        $filter   = $this->buildFilterFromRequest($request, $shopId);
        $cashFlow = app(\App\Reporting\Repositories\FinancialRepository::class)
            ->cashFlow($shopId, $filter);
        $periodLabel = $filter->dateRange->toDisplayString();
        $branchId    = $request->branchId ?? null;

        return view('documents.cash-flow-statement', compact(
            'cashFlow', 'periodLabel', 'branchId'
        ));
    }

    // ── Shared helper ─────────────────────────────────────────────────────────

    private function buildFilterFromRequest(
        Request $request,
        int     $shopId
    ): \App\Reporting\DTOs\ReportFilter {
        if ($request->filled('from') && $request->filled('to')) {
            return \App\Reporting\DTOs\ReportFilter::forShopAndDateRange(
                $shopId, $request->from, $request->to
            );
        }

        $period = \App\Reporting\Enums\ReportPeriod::tryFrom($request->get('period', 'this_month'))
            ?? \App\Reporting\Enums\ReportPeriod::ThisMonth;

        return new \App\Reporting\DTOs\ReportFilter(
            shopId:    $shopId,
            dateRange: $period->toDateRange(),
            branchId:  $request->branchId ? (int) $request->branchId : null,
            period:    $period,
        );
    }

    // ── Sales Report Print ────────────────────────────────────────────────────

    public function salesReportPrint(Request $request)
    {
        $shopId  = Auth::user()->shop_id;
        $filter  = $this->buildFilterFromRequest($request, $shopId);

        $service  = app(\App\Reporting\Services\SalesReportService::class);
        $summary  = $service->summary($filter);
        $trend    = $service->dailyTrend($filter);
        $byProduct= $service->topProducts($filter, 50);

        $periodLabel = $filter->dateRange->toDisplayString();

        return view('documents.report-sales', compact(
            'summary', 'trend', 'byProduct', 'periodLabel'
        ));
    }

    // ── Stock Valuation Print ─────────────────────────────────────────────────

    public function stockValuationPrint(Request $request)
    {
        $shopId       = Auth::user()->shop_id;
        $branchId     = $request->branchId ? (int) $request->branchId : null;
        $valuation    = app(\App\Reporting\Repositories\InventoryRepository::class)
            ->stockValuation(new \App\Reporting\DTOs\ReportFilter(
                shopId:    $shopId,
                dateRange: \App\Reporting\DTOs\DateRange::today(),
                branchId:  $branchId,
            ));
        $summary      = app(\App\Reporting\Repositories\InventoryRepository::class)
            ->totalValue($shopId, $branchId);
        $periodLabel  = now()->format('d M Y H:i');

        return view('documents.report-stock-valuation', compact(
            'valuation', 'summary', 'periodLabel', 'branchId'
        ));
    }

    // ── Customer Due Print ────────────────────────────────────────────────────

    public function customerDuePrint(Request $request)
    {
        $shopId    = Auth::user()->shop_id;
        $custRepo  = app(\App\Reporting\Repositories\CustomerRepository::class);

        $customerDues   = $custRepo->dueAging($shopId);
        $fpReceivables  = $custRepo->fpReceivablesAging($shopId);
        $customerStats  = $custRepo->stats($shopId);
        $periodLabel    = now()->format('d M Y');

        return view('documents.report-customer-due', compact(
            'customerDues', 'fpReceivables', 'customerStats', 'periodLabel'
        ));
    }

    // ── Expense Report Print ──────────────────────────────────────────────────

    public function expenseReportPrint(Request $request)
    {
        $shopId      = Auth::user()->shop_id;
        $filter      = $this->buildFilterFromRequest($request, $shopId);
        $expRepo     = app(\App\Reporting\Repositories\ExpenseRepository::class);

        $aggregate   = $expRepo->aggregate($filter);
        $byCategory  = $expRepo->byCategory($filter);
        $byBranch    = $expRepo->byBranch($filter);
        $periodLabel = $filter->dateRange->toDisplayString();

        return view('documents.report-expenses', compact(
            'aggregate', 'byCategory', 'byBranch', 'periodLabel'
        ));
    }

    // ── Service Report Print ──────────────────────────────────────────────────

    public function serviceReportPrint(Request $request)
    {
        $shopId      = Auth::user()->shop_id;
        $filter      = $this->buildFilterFromRequest($request, $shopId);
        $svcRepo     = app(\App\Reporting\Repositories\ServiceRepository::class);

        $stats         = $svcRepo->stats($shopId);
        $openTickets   = $svcRepo->openTickets($shopId);
        $techPerf      = $svcRepo->technicianPerformance($filter);
        $periodRevenue = $svcRepo->revenueInPeriod($filter);
        $periodLabel   = $filter->dateRange->toDisplayString();

        return view('documents.report-service', compact(
            'stats', 'openTickets', 'techPerf', 'periodRevenue', 'periodLabel'
        ));
    }

    // ── IMEI Ledger Print ─────────────────────────────────────────────────────

    public function imeiLedgerPrint(Request $request)
    {
        $shopId   = Auth::user()->shop_id;
        $filter   = new \App\Reporting\DTOs\ReportFilter(
            shopId:    $shopId,
            dateRange: \App\Reporting\DTOs\DateRange::custom('2000-01-01', now()->toDateString()),
            branchId:  $request->branchId ? (int) $request->branchId : null,
            status:    $request->status ?: null,
            perPage:   9999,
        );

        $records     = app(\App\Reporting\Repositories\InventoryRepository::class)
            ->imeiLedger($filter, $request->q ?? '');
        $periodLabel = now()->format('d M Y H:i');

        return view('documents.report-imei-ledger', compact('records', 'periodLabel'));
    }

    // ── Supplier Statement ────────────────────────────────────────────────────

    public function supplierStatementPrint(Request $request, \App\Models\Supplier $supplier)
    {
        if ($supplier->shop_id !== Auth::user()->shop_id) abort(403);

        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to   = $request->get('to',   now()->toDateString());

        $statement = $this->buildSupplierStatement($supplier, $from, $to);

        return view('documents.supplier-statement', compact('statement'));
    }

    public function supplierStatementPdf(Request $request, \App\Models\Supplier $supplier)
    {
        if ($supplier->shop_id !== Auth::user()->shop_id) abort(403);

        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to   = $request->get('to',   now()->toDateString());

        $statement = $this->buildSupplierStatement($supplier, $from, $to);

        return Pdf::loadView('documents.supplier-statement', compact('statement'))
            ->setPaper('A4', 'portrait')
            ->download("supplier-statement-{$supplier->name}.pdf");
    }

    private function buildSupplierStatement(
        \App\Models\Supplier $supplier,
        string $from,
        string $to
    ): array {
        $purchases = \Illuminate\Support\Facades\DB::table('purchases')
            ->where('supplier_id', $supplier->id)
            ->selectRaw("purchase_date AS txn_date, 'Purchase' AS txn_type,
                reference_number AS reference, total_amount AS debit, 0 AS credit")
            ->get();

        $payments = \Illuminate\Support\Facades\DB::table('supplier_payments')
            ->where('supplier_id', $supplier->id)
            ->selectRaw("payment_date AS txn_date, 'Payment' AS txn_type,
                reference_number AS reference, 0 AS debit, amount AS credit")
            ->get();

        $returns = \Illuminate\Support\Facades\DB::table('purchase_returns')
            ->where('supplier_id', $supplier->id)
            ->selectRaw("return_date AS txn_date, 'Return' AS txn_type,
                return_number AS reference, 0 AS debit, total_amount AS credit")
            ->get();

        $running = 0.0;
        $ledger  = $purchases->concat($payments)->concat($returns)
            ->sortBy('txn_date')
            ->map(function ($row) use (&$running) {
                $running += (float) $row->debit - (float) $row->credit;
                $row->running_balance = $running;
                return $row;
            })->values();

        // Aging
        $today   = now()->toDateString();
        $unpaid  = \Illuminate\Support\Facades\DB::table('purchases')
            ->where('supplier_id', $supplier->id)
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->selectRaw("purchase_date, total_amount, DATEDIFF(?, purchase_date) AS days", [$today])
            ->get();

        $aging = ['current' => 0, '1_30' => 0, '31_60' => 0, '61_90' => 0, 'over_90' => 0];
        foreach ($unpaid as $p) {
            $d = (int) $p->days;
            $a = (float) $p->total_amount;
            if ($d <= 0)      $aging['current'] += $a;
            elseif ($d <= 30) $aging['1_30']    += $a;
            elseif ($d <= 60) $aging['31_60']   += $a;
            elseif ($d <= 90) $aging['61_90']   += $a;
            else              $aging['over_90'] += $a;
        }

        return [
            'supplier'        => $supplier,
            'ledger'          => $ledger,
            'aging'           => $aging,
            'closing_balance' => $running,
            'period_label'    => \Carbon\Carbon::parse($from)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($to)->format('d M Y'),
            'from'            => $from,
            'to'              => $to,
        ];
    }

    // ── Employee Payslip ──────────────────────────────────────────────────────

    public function payrollSlipPrint(\App\Models\PayrollSlip $slip)
    {
        if ($slip->shop_id !== Auth::user()->shop_id) abort(403);

        $slip->load([
            'payrollRun.branch',
            'user',
            'earnings',
            'deductions',
            'activePayments.paymentAccount',
            'loanRecoveries.loan',
        ]);

        $shop = $slip->payrollRun->shop()->withoutGlobalScopes()->findOrFail($slip->shop_id);

        return view('documents.payroll-slip', compact('slip', 'shop'));
    }

    public function payrollSlipPdf(\App\Models\PayrollSlip $slip)
    {
        if ($slip->shop_id !== Auth::user()->shop_id) abort(403);

        $slip->load([
            'payrollRun.branch',
            'user',
            'earnings',
            'deductions',
            'activePayments.paymentAccount',
            'loanRecoveries.loan',
        ]);

        $shop = $slip->payrollRun->shop()->withoutGlobalScopes()->findOrFail($slip->shop_id);

        return Pdf::loadView('documents.payroll-slip', compact('slip', 'shop'))
            ->setPaper('A4', 'portrait')
            ->download("payslip-{$slip->employee_name}-{$slip->payrollRun->monthName()}.pdf");
    }

    // ── Payroll Register ──────────────────────────────────────────────────────

    public function payrollRegisterPrint(\App\Models\PayrollRun $run)
    {
        if ($run->shop_id !== Auth::user()->shop_id) abort(403);

        $run->load([
            'slips.earnings', 'slips.deductions',
            'slips.activePayments.paymentAccount',
            'branch', 'generatedBy', 'approvedBy',
        ]);

        return view('documents.payroll-register', compact('run'));
    }

    public function payrollRegisterPdf(\App\Models\PayrollRun $run)
    {
        if ($run->shop_id !== Auth::user()->shop_id) abort(403);

        $run->load([
            'slips.earnings', 'slips.deductions',
            'slips.activePayments.paymentAccount',
            'branch', 'generatedBy', 'approvedBy',
        ]);

        return Pdf::loadView('documents.payroll-register', compact('run'))
            ->setPaper('A4', 'landscape')
            ->download("{$run->run_number}-register.pdf");
    }
}