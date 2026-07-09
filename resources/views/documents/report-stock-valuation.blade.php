@php $shop = auth()->user()?->shop; @endphp

<x-document.layout title="Stock Valuation Report" :subtitle="'As of ' . $periodLabel" :shop="$shop">
    <x-document.report-header :title="'Stock Valuation Report'" :period="'As of ' . $periodLabel" :branch="$branchId ? \App\Models\Branch::find($branchId)?->name : 'All Branches'" />

    {{-- Summary --}}
    <div class="doc-two-col" style="margin-bottom:4mm;">
        <div>
            <div class="doc-kv-row"><span class="doc-kv-label">Serialized Units</span><span
                    class="doc-kv-value">{{ number_format($summary->serialized_units) }}</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Serialized Value</span><span
                    class="doc-kv-value doc-text-red">৳{{ number_format($summary->serialized_value, 2) }}</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Non-Serialized Value</span><span
                    class="doc-kv-value doc-text-red">৳{{ number_format($summary->non_serialized_value, 2) }}</span>
            </div>
        </div>
        <div style="display:flex;align-items:center;justify-content:center;border:2pt solid #1e3a5f;padding:4mm;">
            <div style="text-align:center;">
                <div style="font-size:7pt;color:#6B7280;text-transform:uppercase;">Total Inventory Value</div>
                <div style="font-size:18pt;font-weight:700;color:#1e3a5f;font-family:var(--doc-mono);">
                    ৳{{ number_format($summary->total_value, 2) }}
                </div>
            </div>
        </div>
    </div>

    <table class="doc-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Brand</th>
                <th>SKU</th>
                <th>Type</th>
                <th class="right">Qty</th>
                <th class="right">Avg Cost (৳)</th>
                <th class="right">Cost Value (৳)</th>
                <th class="right">Retail Value (৳)</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($valuation as $row)
                <tr>
                    <td>{{ $row->product_name }}</td>
                    <td class="muted">{{ $row->brand }}</td>
                    <td class="mono muted">{{ $row->sku }}</td>
                    <td class="center">{{ $row->tracking_type === 'serialized' ? 'IMEI' : 'Qty' }}</td>
                    <td class="right">{{ number_format($row->qty) }}</td>
                    <td class="right mono">{{ number_format($row->avg_cost, 2) }}</td>
                    <td class="right mono doc-text-red">{{ number_format($row->total_cost_value, 2) }}</td>
                    <td class="right mono">{{ number_format($row->retail_value, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td colspan="6">TOTAL</td>
                <td class="right mono">{{ number_format($valuation->sum('total_cost_value'), 2) }}</td>
                <td class="right mono">{{ number_format($valuation->sum('retail_value'), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <x-document.signatures :signatories="[
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => 'Authorized By', 'name' => ''],
    ]" />
</x-document.layout>
