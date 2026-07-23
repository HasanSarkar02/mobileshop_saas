<x-document.layout :title="'Sales Invoice'" :subtitle="$sale->return_processed ? '(Return Processed)' : null" :docNumber="$sale->sale_number" :shop="$sale->shop" :branch="$sale->branch"
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
        'name' => $presenter->isWalkIn ? 'Walk-in Customer' : $sale->customer?->name,
        'lines' => $presenter->isWalkIn
            ? ['No registered customer']
            : array_filter([
                $sale->customer?->phone,
                $sale->customer?->email,
                $sale->customer?->address,
                $sale->customer?->district
                    ? $sale->customer->district . ($sale->customer->thana ? ', ' . $sale->customer->thana : '')
                    : null,
                $sale->customer?->id_number ? 'ID: ' . $sale->customer->id_number : null,
            ]),
    ]" />

    {{-- Items Table --}}
    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th style="width:36%">Description</th>
                <th style="width:13%" class="right">Unit Price</th>
                <th style="width:7%" class="center">Qty</th>
                <th style="width:13%" class="right">Discount</th>
                @if ($presenter->vatAmount > 0)
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
                    @if ($presenter->vatAmount > 0)
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
                <span class="amount">৳{{ number_format($presenter->subtotal, 2) }}</span>
            </div>
            @if ($presenter->totalDiscount > 0)
                <div class="doc-totals-row discount">
                    <span class="label">Total Discount</span>
                    <span class="amount">(৳{{ number_format($presenter->totalDiscount, 2) }})</span>
                </div>
            @endif
            @if ($presenter->vatAmount > 0)
                <div class="doc-totals-row vat">
                    <span class="label">VAT ({{ $sale->shop?->default_vat_rate }}%)</span>
                    <span class="amount">৳{{ number_format($presenter->vatAmount, 2) }}</span>
                </div>
            @endif
            <div class="doc-totals-row grand">
                <span class="label">GRAND TOTAL</span>
                <span class="amount">৳{{ number_format($presenter->grandTotal, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Amount in Words --}}
    <div class="doc-amount-words">
        <strong>Amount in Words: </strong>
        {{ $presenter->amountInWords() }}
    </div>

    {{-- Transaction Settlement Summary --}}
    {{-- Transaction Settlement Summary & Payment Methods --}}
    <div class="doc-section-title">Settlement Summary</div>
    <div class="doc-two-col">
        {{-- Left Column: Invoice & Due Calculations --}}
        <div>
            <div class="doc-kv-row">
                <span class="doc-kv-label">Invoice Total</span>
                <span class="doc-kv-value">৳{{ number_format($presenter->grandTotal, 2) }}</span>
            </div>
            <div class="doc-kv-row">
                <span class="doc-kv-label" style="color:#16a34a;">Paid for this Invoice</span>
                <span class="doc-kv-value"
                    style="color:#16a34a;">৳{{ number_format($presenter->paidAtCheckout, 2) }}</span>
            </div>
            @if ($presenter->dueOnThisInvoice > 0)
                <div class="doc-kv-row">
                    <span class="doc-kv-label doc-text-bold" style="color:#dc2626;">This Invoice Due</span>
                    <span class="doc-kv-value doc-text-bold"
                        style="color:#dc2626;">৳{{ number_format($presenter->dueOnThisInvoice, 2) }}</span>
                </div>
            @endif

            {{-- Due History for Registered Customers --}}
            @if (!$presenter->isWalkIn)
                <div style="border-top:1pt solid #e5e7eb;margin-top:2mm;padding-top:2mm;">
                    <div class="doc-kv-row">
                        <span class="doc-kv-label">Previous Due</span>
                        <span class="doc-kv-value">৳{{ number_format($sale->previous_due ?? 0, 2) }}</span>
                    </div>

                    @if ($sale->due_collection_amount > 0)
                        <div class="doc-kv-row">
                            <span class="doc-kv-label">Previous Due Collected</span>
                            <span class="doc-kv-value" style="color:#16a34a;">-
                                ৳{{ number_format($sale->due_collection_amount, 2) }}</span>
                        </div>
                    @endif

                    @php
                        // Total Outstanding = Previous Due - Amount Collected + Any new due created on this exact invoice
                        $totalOutstanding =
                            ($sale->previous_due ?? 0) -
                            ($sale->due_collection_amount ?? 0) +
                            $presenter->dueOnThisInvoice;
                    @endphp

                    <div class="doc-kv-row" style="border-top:1pt solid #1e3a5f;margin-top:1mm;padding-top:1mm;">
                        <span class="doc-kv-label doc-text-bold" style="color:#dc2626;">Total Outstanding Balance</span>
                        <span class="doc-kv-value doc-text-bold"
                            style="color:#dc2626;">৳{{ number_format($totalOutstanding, 2) }}</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Column: Payment Status & Methods --}}
        <div>
            <div class="doc-kv-row">
                <span class="doc-kv-label doc-text-bold">Payment Status</span>
                @if ($presenter->isFullyPaid)
                    <span class="doc-kv-value doc-text-bold" style="color:#16a34a;">PAID</span>
                @else
                    <span class="doc-kv-value doc-text-bold" style="color:#dc2626;">PARTIAL / UNPAID</span>
                @endif
            </div>

            {{-- Payment Methods Moved Here --}}
            @if ($presenter->paymentMethods->isNotEmpty())
                <div style="border-top:1pt solid #e5e7eb;margin-top:2mm;padding-top:2mm;">
                    <span class="doc-kv-label doc-text-bold" style="display:block; margin-bottom:2mm;">Payment
                        Methods:</span>
                    @foreach ($presenter->paymentMethods as $pmt)
                        <div class="doc-kv-row">
                            <span class="doc-kv-label">
                                {{ $pmt['label'] }}
                                @if ($pmt['reference'])
                                    <small style="color:#9CA3AF;">(Ref: {{ $pmt['reference'] }})</small>
                                @endif
                            </span>
                            <span class="doc-kv-value">৳{{ number_format($pmt['amount'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Notes --}}
    @if ($sale->notes)
        <div class="doc-notes">
            <span class="doc-notes-label">Notes</span>
            {{ $sale->notes }}
        </div>
    @endif

    {{-- Watermark Stamp --}}
    @if ($presenter->isFullyPaid && $sale->status->value === 'confirmed')
        <div class="doc-stamp-container">
            <div class="doc-stamp doc-stamp-paid">PAID</div>
        </div>
    @elseif (!$presenter->isFullyPaid)
        <div class="doc-stamp-container">
            <div class="doc-stamp doc-stamp-partial">PARTIAL</div>
        </div>
    @endif

    {{-- Signatures --}}
    <x-document.signatures :signatories="$presenter->signatories" />

</x-document.layout>
