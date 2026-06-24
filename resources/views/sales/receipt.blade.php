<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $sale->sale_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            color: #111;
            background: #fff;
        }

        .page {
            max-width: 210mm;
            margin: 0 auto;
            padding: 12mm 14mm;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6mm;
            padding-bottom: 4mm;
            border-bottom: 2px solid #4f46e5;
        }

        .shop-name {
            font-size: 22px;
            font-weight: 800;
            color: #4f46e5;
        }

        .shop-meta {
            font-size: 11px;
            color: #555;
            margin-top: 2px;
            line-height: 1.5;
        }

        .invoice-info {
            text-align: right;
        }

        .invoice-number {
            font-size: 16px;
            font-weight: 700;
            color: #4f46e5;
        }

        .invoice-meta {
            font-size: 11px;
            color: #555;
            line-height: 1.6;
            margin-top: 2px;
        }

        /* Customer & Payment row */
        .info-row {
            display: flex;
            gap: 8mm;
            margin-bottom: 5mm;
        }

        .info-box {
            flex: 1;
            background: #f8f8ff;
            border-left: 3px solid #4f46e5;
            padding: 3mm 4mm;
            border-radius: 2mm;
        }

        .info-box-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #4f46e5;
            margin-bottom: 2mm;
        }

        .info-line {
            font-size: 11px;
            color: #333;
            line-height: 1.5;
        }

        .info-line strong {
            font-weight: 600;
        }

        /* Items table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5mm;
        }

        thead th {
            background: #4f46e5;
            color: white;
            padding: 2.5mm 3mm;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
        }

        thead th.right {
            text-align: right;
        }

        tbody tr:nth-child(even) {
            background: #f9f9ff;
        }

        tbody td {
            padding: 2mm 3mm;
            font-size: 11px;
            vertical-align: top;
            border-bottom: 0.5px solid #eee;
        }

        td.right {
            text-align: right;
        }

        td.center {
            text-align: center;
        }

        .item-name {
            font-weight: 600;
        }

        .item-sub {
            font-size: 10px;
            color: #666;
            margin-top: 1px;
        }

        .item-imei {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            color: #4f46e5;
        }

        /* Totals */
        .totals {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 5mm;
        }

        .totals-table {
            min-width: 70mm;
        }

        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 1.5mm 0;
            font-size: 11.5px;
            border-bottom: 0.5px solid #eee;
            gap: 8mm;
        }

        .totals-row:last-child {
            border-bottom: none;
        }

        .totals-row.grand {
            font-size: 14px;
            font-weight: 700;
            color: #4f46e5;
            padding: 2.5mm 0;
            border-top: 2px solid #4f46e5;
            margin-top: 1mm;
        }

        .totals-row.discount {
            color: #dc2626;
        }

        .totals-row.change {
            color: #16a34a;
            font-weight: 600;
        }

        /* Payment section */
        .payment-section {
            display: flex;
            gap: 8mm;
            margin-bottom: 5mm;
        }

        .payment-box {
            flex: 1;
            background: #f0fdf4;
            border-radius: 2mm;
            padding: 3mm 4mm;
            border: 1px solid #bbf7d0;
        }

        .due-box {
            flex: 1;
            background: #fff7ed;
            border-radius: 2mm;
            padding: 3mm 4mm;
            border: 1px solid #fed7aa;
        }

        .box-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #166534;
            letter-spacing: 0.5px;
            margin-bottom: 2mm;
        }

        .due-box .box-title {
            color: #9a3412;
        }

        .payment-line {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            padding: 1mm 0;
        }

        /* Finance partner */
        .fp-box {
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 2mm;
            padding: 3mm 4mm;
            margin-bottom: 4mm;
        }

        .fp-title {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1e40af;
            letter-spacing: 0.5px;
            margin-bottom: 1.5mm;
        }

        .fp-line {
            font-size: 11px;
        }

        /* Footer */
        .footer {
            text-align: center;
            padding-top: 4mm;
            border-top: 1px dashed #ddd;
        }

        .footer-main {
            font-size: 13px;
            font-weight: 700;
            color: #4f46e5;
            margin-bottom: 2mm;
        }

        .footer-sub {
            font-size: 10px;
            color: #888;
            line-height: 1.6;
        }

        .qr-hint {
            margin-top: 3mm;
            font-size: 10px;
            color: #aaa;
        }

        /* Stamp */
        .paid-stamp {
            position: absolute;
            right: 20mm;
            bottom: 35mm;
            border: 3px solid #16a34a;
            color: #16a34a;
            font-weight: 900;
            font-size: 18px;
            padding: 2mm 5mm;
            border-radius: 2mm;
            transform: rotate(-15deg);
            opacity: 0.6;
            text-transform: uppercase;
            pointer-events: none;
        }

        /* Print */
        @media print {
            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }

            .page {
                padding: 8mm 10mm;
            }
        }

        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #4f46e5;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
        }

        .print-btn:hover {
            background: #4338ca;
        }
    </style>
</head>

<body>
    <div class="page" style="position:relative;">

        {{-- PAID stamp --}}
        @if ($sale->status->value === 'confirmed')
            <div class="paid-stamp">Paid</div>
        @endif

        {{-- Header --}}
        <div class="header">
            <div>
                <div class="shop-name">{{ $sale->shop->name ?? 'ShopSaaS' }}</div>
                <div class="shop-meta">
                    @if ($sale->shop->address)
                        {{ $sale->shop->address }}<br>
                    @endif
                    @if ($sale->shop->phone)
                        Tel: {{ $sale->shop->phone }}<br>
                    @endif
                    @if ($sale->shop->email)
                        {{ $sale->shop->email }}
                    @endif
                    @if ($sale->shop->vat_enabled && $sale->shop->vat_registration_number)
                        <br>VAT Reg: {{ $sale->shop->vat_registration_number }}
                    @endif
                </div>
            </div>
            <div class="invoice-info">
                <div class="invoice-number">{{ $sale->sale_number }}</div>
                <div class="invoice-meta">
                    Date: {{ $sale->confirmed_at?->format('d M Y, h:i A') }}<br>
                    Branch: {{ $sale->branch?->name }}<br>
                    Cashier: {{ $sale->cashier?->name }}<br>
                    Status: <strong>{{ $sale->status->label() }}</strong>
                </div>
            </div>
        </div>

        {{-- Customer + Payment Method summary --}}
        <div class="info-row">
            <div class="info-box">
                <div class="info-box-title">Bill To</div>
                @if ($sale->customer?->customer_type?->value !== 'walk_in')
                    <div class="info-line"><strong>{{ $sale->customer?->name }}</strong></div>
                    <div class="info-line">{{ $sale->customer?->phone }}</div>
                    @if ($sale->customer?->address)
                        <div class="info-line">{{ $sale->customer->address }}</div>
                    @endif
                @else
                    <div class="info-line"><strong>Walk-in Customer</strong></div>
                    <div class="info-line" style="color:#999">No registered customer</div>
                @endif
            </div>
            @if ($sale->notes)
                <div class="info-box">
                    <div class="info-box-title">Notes</div>
                    <div class="info-line">{{ $sale->notes }}</div>
                </div>
            @endif
        </div>

        {{-- Items table --}}
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th class="right">Unit Price</th>
                    <th class="center">Qty</th>
                    <th class="right">Discount</th>
                    @if ($sale->vat_amount > 0)
                        <th class="right">VAT</th>
                    @endif
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $i => $item)
                    <tr>
                        <td class="center" style="color:#999">{{ $i + 1 }}</td>
                        <td>
                            <div class="item-name">{{ $item->product_name }}</div>
                            @if ($item->variant_label)
                                <div class="item-sub">{{ $item->variant_label }}</div>
                            @endif
                            @if ($item->serial_number)
                                <div class="item-imei">IMEI: {{ $item->serial_number }}</div>
                            @endif
                            <div class="item-sub" style="color:#aaa">SKU: {{ $item->sku }}</div>
                        </td>
                        <td class="right">৳{{ number_format($item->unit_price, 2) }}</td>
                        <td class="center">{{ $item->quantity }}</td>
                        <td class="right" style="color:#dc2626">
                            @if ($item->discount_amount > 0)
                                −৳{{ number_format($item->discount_amount, 2) }}
                                @if ($item->discount_type === 'percentage')
                                    <br><span style="font-size:9px;color:#aaa">({{ $item->discount_value }}%)</span>
                                @endif
                            @else
                                <span style="color:#ccc">—</span>
                            @endif
                        </td>
                        @if ($sale->vat_amount > 0)
                            <td class="right">
                                @if ($item->vat_amount > 0)
                                    ৳{{ number_format($item->vat_amount, 2) }}
                                @else
                                    <span style="color:#ccc">—</span>
                                @endif
                            </td>
                        @endif
                        <td class="right" style="font-weight:600">৳{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <div class="totals-table">
                <div class="totals-row">
                    <span style="color:#555">Subtotal</span>
                    <span>৳{{ number_format($sale->subtotal, 2) }}</span>
                </div>
                @if ($sale->total_discount_amount > 0)
                    <div class="totals-row discount">
                        <span>Total Discount</span>
                        <span>−৳{{ number_format($sale->total_discount_amount, 2) }}</span>
                    </div>
                @endif
                @if ($sale->vat_amount > 0)
                    <div class="totals-row">
                        <span style="color:#555">VAT</span>
                        <span>৳{{ number_format($sale->vat_amount, 2) }}</span>
                    </div>
                @endif
                <div class="totals-row grand">
                    <span>GRAND TOTAL</span>
                    <span>৳{{ number_format($sale->grand_total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Finance Partner --}}
        @if ($sale->financePartnerReceivable)
            @php $fp = $sale->financePartnerReceivable; @endphp
            <div class="fp-box">
                <div class="fp-title">EMI / Finance Partner</div>
                <div class="fp-line">
                    <strong>{{ $fp->financePartner?->name }}</strong> —
                    Receivable: <strong>৳{{ number_format($fp->total_amount, 2) }}</strong> |
                    Status: {{ $fp->status->label() }}
                    @if ($fp->partner_reference)
                        | Ref: {{ $fp->partner_reference }}
                    @endif
                </div>
            </div>
        @endif

        {{-- Payment breakdown + Due section --}}
        <div class="payment-section">
            {{-- How they paid --}}
            <div class="payment-box">
                <div class="box-title">Payment Received</div>
                @foreach ($sale->payments as $pmt)
                    <div class="payment-line">
                        <span>
                            {{ $pmt->paymentAccount?->name ?? ($pmt->financePartner?->name ?? ucfirst(str_replace('_', ' ', $pmt->payment_type))) }}
                            @if ($pmt->reference_number)
                                <span style="font-size:9px;color:#888">({{ $pmt->reference_number }})</span>
                            @endif
                        </span>
                        <strong>৳{{ number_format($pmt->amount, 2) }}</strong>
                    </div>
                @endforeach
            </div>

            {{-- Due balance info --}}
            @if ($sale->customer?->customer_type?->value !== 'walk_in')
                @php
                    $bakiThisSale = $sale->payments->where('payment_type', 'customer_credit')->sum('amount');
                    $prevBalance = $sale->customer?->current_balance ?? 0;
                @endphp
                @if ($bakiThisSale > 0 || $sale->due_collection_amount > 0 || $prevBalance > 0)
                    <div class="due-box">
                        <div class="box-title" style="color:#9a3412">Customer Balance</div>
                        @if ($bakiThisSale > 0)
                            <div class="payment-line">
                                <span>Baki this sale</span>
                                <strong style="color:#dc2626">+৳{{ number_format($bakiThisSale, 2) }}</strong>
                            </div>
                        @endif
                        @if ($sale->due_collection_amount > 0)
                            <div class="payment-line">
                                <span>Due collected</span>
                                <strong
                                    style="color:#16a34a">−৳{{ number_format($sale->due_collection_amount, 2) }}</strong>
                            </div>
                        @endif
                        <div class="payment-line"
                            style="border-top: 0.5px solid #fed7aa; margin-top:1mm; padding-top:1mm;">
                            <span>Remaining balance</span>
                            <strong style="color:#dc2626">৳{{ number_format($prevBalance, 2) }}</strong>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        {{-- Footer --}}
        <div class="footer">
            <div class="footer-main">Thank you for your purchase! 🙏</div>
            <div class="footer-sub">
                Return / Exchange policy: 7 days with original receipt | Items must be in original condition
                @if ($sale->shop->phone)
                    <br>For support, call: {{ $sale->shop->phone }}
                @endif
            </div>
            <div class="qr-hint">Invoice: {{ $sale->sale_number }} | {{ $sale->confirmed_at?->format('d M Y') }}</div>
        </div>
    </div>

    {{-- Print button (no-print) --}}
    <button class="print-btn no-print" onclick="window.print()">🖨 Print Receipt</button>

    <script>
        // Auto-print when opened in a new tab via POS complete screen
        window.onload = function() {
            if (window.location.hash === '#autoprint') {
                setTimeout(() => window.print(), 500);
            }
        };
    </script>
</body>

</html>
