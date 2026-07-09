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

    public function saleInvoice(Sale $sale)
    {
        $this->shopGuard($sale->shop_id);
        $sale->load([
            'shop', 'branch', 'cashier', 'customer',
            'items', 'payments.paymentAccount', 'payments.financePartner',
            'financePartnerReceivable.financePartner',
        ]);
        return view('documents.sale-invoice', compact('sale'));
    }

    public function saleInvoicePdf(Sale $sale)
    {
        $this->shopGuard($sale->shop_id);
        $sale->load([
            'shop', 'branch', 'cashier', 'customer',
            'items', 'payments.paymentAccount', 'payments.financePartner',
            'financePartnerReceivable.financePartner',
        ]);
        $pdf = Pdf::loadView('documents.sale-invoice', compact('sale'))
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
}