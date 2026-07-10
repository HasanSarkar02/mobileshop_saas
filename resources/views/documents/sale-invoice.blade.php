@php
    $shop = $sale->shop;
    $branch = $sale->branch;
    $customer = $sale->customer;
    $isWalkIn = $customer?->customer_type?->value === 'walk_in';
    $bakiTotal = $sale->payments->where('payment_type', 'customer_credit')->sum('amount');
    $fpTotal = $sale->payments->where('payment_type', 'finance_partner')->sum('amount');

    function docNumberToWords(float $amount): string
    {
        // Simple version — just formats the number with "Taka" label
        return 'Taka ' . number_format($amount, 2) . ' Only';
    }

    $signatories = [
        ['title' => 'Prepared By', 'name' => $sale->cashier?->name ?? ''],
        ['title' => "Customer's Signature", 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp

<x-document.layout :title="'Sales Invoice'" :subtitle="$sale->return_processed ? '(Return Processed)' : null" :docNumber="$sale->sale_number" :shop="$shop" :branch="$branch"
    :exportPdfUrl="route('documents.sale.pdf', $sale)">

    {{-- Meta Band --}}
    <x-document.meta :cols="4" :items="[
        ['label' => 'Invoice Date', 'value' => $sale->confirmed_at?->format('d M Y')],
        ['label' => 'Invoice Time', 'value' => $sale->confirmed_at?->format('H:i A')],
        ['label' => 'Cashier', 'value' => $sale->cashier?->name],
        ['label' => 'Status', 'value' => $sale->status->label()],
    ]" />

    {{-- Bill To --}}
    <x-document.parties :to="[
        'title' => 'Bill To',
        'name' => $isWalkIn ? 'Walk-in Customer' : $customer?->name,
        'lines' => $isWalkIn
            ? ['No registered customer']
            : [
                $customer?->phone,
                $customer?->email,
                $customer?->address,
                $customer?->district ? $customer->district . ($customer->thana ? ', ' . $customer->thana : '') : null,
                $customer?->id_number ? 'ID: ' . $customer->id_number : null,
            ],
    ]" :extra="!$isWalkIn && $customer?->current_balance > 0
        ? [
            'title' => 'Account Summary',
            'lines' => [
                'Previous Balance: ৳' . number_format($customer->current_balance, 2),
                $customer->credit_limit > 0
                    ? 'Credit Limit: ৳' . number_format($customer->credit_limit, 2)
                    : 'Credit Limit: Unlimited',
                'Customer Since: ' . $customer->created_at->format('d M Y'),
            ],
        ]
        : null" />

    {{-- Items Table --}}
    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:36%">Description</th>
                <th style="width:13%" class="right">Unit Price</th>
                <th style="width:7%" class="center">Qty</th>
                <th style="width:13%" class="right">Discount</th>
                @if ($sale->vat_amount > 0)
                    <th style="width:13%" class="right">VAT</th>
                @endif
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $i => $item)
                <tr>
                    <td class="center muted">{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if ($item->variant_label)
                            <br><span style="font-size:7.5pt;color:#6B7280;">{{ $item->variant_label }}</span>
                        @endif
                        @if ($item->serial_number)
                            <br><span class="mono" style="font-size:7.5pt;color:#1e40af;">IMEI:
                                {{ $item->serial_number }}</span>
                        @endif
                        <br><span class="muted">SKU: {{ $item->sku }}</span>
                    </td>
                    <td class="right mono">৳{{ number_format($item->unit_price, 2) }}</td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right doc-text-red">
                        @if ($item->discount_amount > 0)
                            (৳{{ number_format($item->discount_amount, 2) }})
                            @if ($item->discount_type === 'percentage')
                                <br><span style="font-size:7pt;">({{ $item->discount_value }}%)</span>
                            @endif
                        @else
                            —
                        @endif
                    </td>
                    @if ($sale->vat_amount > 0)
                        <td class="right">
                            {{ $item->vat_amount > 0 ? '৳' . number_format($item->vat_amount, 2) : '—' }}
                        </td>
                    @endif
                    <td class="right mono doc-text-bold">৳{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="doc-totals">
        <div class="doc-totals-table">
            <div class="doc-totals-row">
                <span class="label">Subtotal</span>
                <span class="amount">৳{{ number_format($sale->subtotal, 2) }}</span>
            </div>
            @if ($sale->total_discount_amount > 0)
                <div class="doc-totals-row discount">
                    <span class="label">Total Discount</span>
                    <span class="amount">(৳{{ number_format($sale->total_discount_amount, 2) }})</span>
                </div>
            @endif
            @if ($sale->vat_amount > 0)
                <div class="doc-totals-row vat">
                    <span class="label">VAT ({{ $shop?->default_vat_rate }}%)</span>
                    <span class="amount">৳{{ number_format($sale->vat_amount, 2) }}</span>
                </div>
            @endif
            <div class="doc-totals-row grand">
                <span class="label">GRAND TOTAL</span>
                <span class="amount">৳{{ number_format($sale->grand_total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Amount in Words --}}
    <div class="doc-amount-words">
        <strong>Amount in Words: </strong>
        {{ docNumberToWords((float) $sale->grand_total) }}
    </div>

    {{-- Payment Breakdown --}}
    @if ($sale->payments->isNotEmpty())
        <div class="doc-section-title">Payment Details</div>
        <div class="doc-two-col">
            <div>
                @foreach ($sale->payments as $pmt)
                    <div class="doc-kv-row">
                        <span class="doc-kv-label">
                            {{ $pmt->paymentAccount?->name ??
                                ($pmt->financePartner?->name ?? ucfirst(str_replace('_', ' ', $pmt->payment_type))) }}
                            @if ($pmt->reference_number)
                                <small style="color:#9CA3AF;">(Ref: {{ $pmt->reference_number }})</small>
                            @endif
                        </span>
                        <span class="doc-kv-value">৳{{ number_format($pmt->amount, 2) }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Customer Balance Impact --}}
            @if (!$isWalkIn && ($bakiTotal > 0 || $sale->due_collection_amount > 0))
                <div>
                    <div class="doc-kv-row">
                        <span class="doc-kv-label" style="color:#dc2626;">Credit / Due this sale</span>
                        <span class="doc-kv-value" style="color:#dc2626;">৳{{ number_format($bakiTotal, 2) }}</span>
                    </div>
                    @if ($sale->due_collection_amount > 0)
                        <div class="doc-kv-row">
                            <span class="doc-kv-label" style="color:#16a34a;">Due Collected</span>
                            <span class="doc-kv-value"
                                style="color:#16a34a;">৳{{ number_format($sale->due_collection_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="doc-kv-row" style="border-top:1pt solid #1e3a5f;margin-top:1mm;padding-top:1mm;">
                        <span class="doc-kv-label">Remaining Balance</span>
                        <span class="doc-kv-value"
                            style="color:#dc2626;">৳{{ number_format($customer?->current_balance ?? 0, 2) }}</span>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- Finance Partner --}}
    @if ($sale->financePartnerReceivable)
        @php $fpr = $sale->financePartnerReceivable; @endphp
        <div class="doc-section-title">Finance Partner — EMI Details</div>
        <div class="doc-two-col">
            <div>
                <div class="doc-kv-row"><span class="doc-kv-label">Partner</span><span
                        class="doc-kv-value">{{ $fpr->financePartner?->name }}</span></div>
                <div class="doc-kv-row"><span class="doc-kv-label">EMI Receivable</span><span
                        class="doc-kv-value">৳{{ number_format($fpr->total_amount, 2) }}</span></div>
                <div class="doc-kv-row"><span class="doc-kv-label">Status</span><span
                        class="doc-kv-value">{{ $fpr->status->label() }}</span></div>
            </div>
        </div>
    @endif

    {{-- Notes --}}
    @if ($sale->notes)
        <div class="doc-notes">
            <span class="doc-notes-label">Notes</span>
            {{ $sale->notes }}
        </div>
    @endif

    {{-- Paid Stamp --}}
    @if ($bakiTotal <= 0 && $sale->status->value === 'confirmed')
        <div class="doc-stamp-container">
            <div class="doc-stamp doc-stamp-paid">PAID</div>
        </div>
    @elseif($bakiTotal > 0)
        <div class="doc-stamp-container">
            <div class="doc-stamp doc-stamp-partial">PARTIAL</div>
        </div>
    @endif

    {{-- Signatures --}}
    <x-document.signatures :signatories="$signatories" />

    {{-- Return Policy --}}
    @if ($shop?->document_footer_note)
        <div class="doc-notes" style="margin-top:3mm;">{{ $shop->document_footer_note }}</div>
    @else
        <div class="doc-notes" style="margin-top:3mm;">
            <strong>Return Policy:</strong> Returns accepted within 7 days with original invoice.
            Items must be in original condition with all accessories.
        </div>
    @endif

</x-document.layout>
