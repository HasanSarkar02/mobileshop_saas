@php $shop = auth()->user()?->shop; @endphp

<x-document.layout title="IMEI Ledger" :subtitle="'As of ' . $periodLabel" :shop="$shop" :landscape="true">
    <x-document.report-header :title="'IMEI / Serialized Unit Ledger'" :period="'As of ' . $periodLabel" :branch="'All Branches'" />

    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:14%">IMEI / Serial</th>
                <th style="width:18%">Product</th>
                <th style="width:8%">Brand</th>
                <th style="width:8%">Status</th>
                <th style="width:8%">Branch</th>
                <th class="right" style="width:9%">Cost (৳)</th>
                <th style="width:10%">Received</th>
                <th style="width:10%">Sale</th>
                <th style="width:15%">Customer</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($records as $row)
                <tr>
                    <td class="mono" style="font-size:7pt;">{{ $row->serial_number }}</td>
                    <td>{{ $row->product_name }}<br><span class="muted">{{ $row->sku }}</span></td>
                    <td class="muted">{{ $row->brand }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                    <td class="muted">{{ $row->branch_name ?? '—' }}</td>
                    <td class="right mono">{{ number_format($row->cost_price, 2) }}</td>
                    <td>{{ \Carbon\Carbon::parse($row->received_at)->format('d M Y') }}</td>
                    <td class="mono" style="font-size:7pt;">{{ $row->sale_number ?? '—' }}</td>
                    <td>{{ $row->customer_name ?? ($row->sale_number ? 'Walk-in' : '—') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <x-document.signatures :signatories="[
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => 'Authorized By', 'name' => ''],
    ]" />
</x-document.layout>
