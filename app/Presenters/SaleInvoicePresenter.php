<?php

namespace App\Presenters;

use App\Models\Sale;
use Illuminate\Support\Collection;

class SaleInvoicePresenter
{
    public float $subtotal;
    public float $totalDiscount;
    public float $vatAmount;
    public float $grandTotal;
    public float $paidAtCheckout;
    public float $dueOnThisInvoice;
    public bool $isFullyPaid;
    public bool $isWalkIn;
    public Collection $paymentMethods;
    public array $signatories;

    public function __construct(public readonly Sale $sale)
    {
        $this->subtotal = (float) $sale->subtotal;
        $this->totalDiscount = (float) $sale->total_discount_amount;
        $this->vatAmount = (float) $sale->vat_amount;
        $this->grandTotal = (float) $sale->grand_total;
        $this->isWalkIn = $sale->customer?->customer_type?->value === 'walk_in';

        $this->parsePayments();
        $this->buildSignatories();
    }

    private function parsePayments(): void
    {
        // Portion added to customer credit balance for THIS invoice
        $creditPortion = (float) $this->sale->payments
            ->where('payment_type', 'customer_credit')
            ->sum('amount');

        // All direct payments applied at checkout (Cash, Card, MFS, Installment)
        $directPayments = $this->sale->payments
            ->where('payment_type', '!=', 'customer_credit');

        $this->paidAtCheckout = (float) $directPayments->sum('amount');
        $this->dueOnThisInvoice = round($creditPortion, 2);

        // Fallback for legacy sales where payment breakdown wasn't explicitly logged
        if ($this->dueOnThisInvoice <= 0 && $this->paidAtCheckout < $this->grandTotal) {
            $this->dueOnThisInvoice = max(0.0, round($this->grandTotal - $this->paidAtCheckout, 2));
        }

        $this->isFullyPaid = $this->dueOnThisInvoice <= 0.0;

        // Mask internal finance workflow into customer-friendly payment method names
        $this->paymentMethods = $directPayments->map(function ($pmt) {
            $isFinance = $pmt->payment_type === 'finance_partner';

            return [
                'label' => $isFinance
                    ? 'Installment Financing'
                    : ($pmt->paymentAccount?->name ?? ucfirst(str_replace('_', ' ', $pmt->payment_type))),
                'reference' => $isFinance ? null : $pmt->reference_number,
                'amount' => (float) $pmt->amount,
            ];
        });
    }

    private function buildSignatories(): void
    {
        $this->signatories = [
            ['title' => 'Prepared By', 'name' => $this->sale->cashier?->name ?? ''],
            ['title' => "Customer's Signature", 'name' => ''],
            ['title' => 'Authorized By', 'name' => ''],
        ];
    }

    public function amountInWords(): string
    {
        return 'Taka ' . number_format($this->grandTotal, 2) . ' Only';
    }
}