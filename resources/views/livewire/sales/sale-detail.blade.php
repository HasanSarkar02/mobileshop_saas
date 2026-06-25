<div class="max-w-4xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex-1">
            <div class="font-mono font-bold text-indigo-700 text-xl">{{ $sale->sale_number }}</div>
            <div class="text-gray-500 text-sm mt-1 flex flex-wrap gap-3">
                <span>{{ $sale->confirmed_at?->format('d M Y, H:i') }}</span>
                <span>Branch: {{ $sale->branch?->name }}</span>
                <span>Cashier: {{ $sale->cashier?->name }}</span>
            </div>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <span class="badge {{ $sale->status->badgeClass() }}">{{ $sale->status->label() }}</span>
            <span class="text-2xl font-bold text-indigo-700">৳{{ number_format($sale->grand_total, 2) }}</span>
        </div>
    </div>

    {{-- Customer --}}
    <div class="card p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
            <span class="font-bold text-indigo-600">{{ strtoupper(substr($sale->customer?->name ?? 'W', 0, 1)) }}</span>
        </div>
        <div class="flex-1">
            @if ($sale->customer?->customer_type?->value !== 'walk_in')
                <div class="font-semibold text-gray-900">{{ $sale->customer?->name }}</div>
                <div class="text-sm text-gray-500">{{ $sale->customer?->phone }}</div>
            @else
                <div class="font-semibold text-gray-500">Walk-in Customer</div>
            @endif
        </div>
        @if ($sale->isVoidable())
            <div class="flex gap-2">
                <a href="{{ route('sales.return', $sale) }}" wire:navigate class="btn-secondary btn-sm">
                    ↩ Process Return
                </a>
            </div>
        @endif
    </div>

    {{-- Items --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 text-sm">Items ({{ $sale->items->count() }})</h3>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="table-th">Product</th>
                    <th class="table-th text-right">Price</th>
                    <th class="table-th text-center">Qty</th>
                    <th class="table-th text-right">Discount</th>
                    <th class="table-th text-right">Total</th>
                    <th class="table-th text-right">Profit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach ($sale->items as $item)
                    <tr>
                        <td class="table-td">
                            <div class="font-semibold text-sm">{{ $item->product_name }}</div>
                            @if ($item->variant_label)
                                <div class="text-xs text-gray-500">{{ $item->variant_label }}</div>
                            @endif
                            @if ($item->serial_number)
                                <div class="text-xs font-mono text-indigo-500">{{ $item->serial_number }}</div>
                            @endif
                            <div class="text-xs text-gray-400">{{ $item->sku }}</div>
                        </td>
                        <td class="table-td text-right">৳{{ number_format($item->unit_price, 2) }}</td>
                        <td class="table-td text-center">{{ $item->quantity }}</td>
                        <td class="table-td text-right text-red-500">
                            {{ $item->discount_amount > 0 ? '−৳' . number_format($item->discount_amount, 2) : '—' }}
                        </td>
                        <td class="table-td text-right font-bold">৳{{ number_format($item->line_total, 2) }}</td>
                        <td
                            class="table-td text-right {{ $item->profit_amount >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium text-sm">
                            {{ $item->profit_amount >= 0 ? '+' : '' }}৳{{ number_format($item->profit_amount, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-100">
            <div class="flex justify-end">
                <div class="space-y-1.5 min-w-[220px] text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span><span>৳{{ number_format($sale->subtotal, 2) }}</span></div>
                    @if ($sale->total_discount_amount > 0)
                        <div class="flex justify-between text-red-500">
                            <span>Discount</span><span>−৳{{ number_format($sale->total_discount_amount, 2) }}</span>
                        </div>
                    @endif
                    @if ($sale->vat_amount > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>VAT</span><span>৳{{ number_format($sale->vat_amount, 2) }}</span></div>
                    @endif
                    <div class="flex justify-between font-bold text-base border-t border-gray-200 pt-1.5">
                        <span>Grand Total</span>
                        <span class="text-indigo-700">৳{{ number_format($sale->grand_total, 2) }}</span>
                    </div>
                    <div
                        class="flex justify-between text-sm {{ $sale->gross_profit >= 0 ? 'text-green-600' : 'text-red-600' }}">
                        <span>Gross Profit</span>
                        <span>{{ $sale->gross_profit >= 0 ? '+' : '' }}৳{{ number_format($sale->gross_profit, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payments --}}
    <div class="card p-5">
        <h3 class="font-semibold text-gray-900 text-sm mb-3">Payment Breakdown</h3>
        <div class="space-y-2">
            @foreach ($sale->payments as $pmt)
                <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                    <div class="text-sm text-gray-700">
                        {{ $pmt->paymentAccount?->name ?? ($pmt->financePartner?->name ?? ucfirst(str_replace('_', ' ', $pmt->payment_type))) }}
                        @if ($pmt->reference_number)
                            <span class="text-xs text-gray-400 ml-1">({{ $pmt->reference_number }})</span>
                        @endif
                    </div>
                    <span class="font-semibold text-gray-900">৳{{ number_format($pmt->amount, 2) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Finance Partner Receivable --}}
    @if ($sale->financePartnerReceivable)
        @php $fpr = $sale->financePartnerReceivable; @endphp
        <div class="card p-5 bg-blue-50 border-blue-200">
            <h3 class="font-semibold text-blue-900 text-sm mb-2">Finance Partner Receivable</h3>
            <div class="grid sm:grid-cols-3 gap-3 text-sm">
                <div><span class="text-blue-600">Partner</span>
                    <div class="font-bold text-blue-900">{{ $fpr->financePartner?->name }}</div>
                </div>
                <div><span class="text-blue-600">Total Due</span>
                    <div class="font-bold text-blue-900">৳{{ number_format($fpr->total_amount, 2) }}</div>
                </div>
                <div><span class="text-blue-600">Status</span>
                    <div><span class="badge {{ $fpr->status->badgeClass() }}">{{ $fpr->status->label() }}</span></div>
                </div>
            </div>
        </div>
    @endif

    {{-- Actions --}}
    <div class="flex gap-3 flex-wrap">
        <a href="{{ route('sales.receipt', $sale) }}" target="_blank" class="btn-secondary btn-sm">
            🖨 Print Receipt
        </a>
        <a href="{{ route('sales.index') }}" wire:navigate class="btn-secondary btn-sm">← Back to Sales</a>
    </div>
</div>
