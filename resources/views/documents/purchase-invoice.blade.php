@php
    $shop = $purchase->shop ?? auth()->user()?->shop;
    $branch = $purchase->branch;
    $supplier = $purchase->supplier;

    $signatories = [
        ['title' => 'Supplier Signature', 'name' => ''],
        ['title' => 'Received By', 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp

<x-document.layout title="Purchase Invoice" :docNumber="$purchase->reference_number" :shop="$shop" :branch="$branch" :exportPdfUrl="route('documents.purchase.pdf', $purchase)">
    <x-document.meta :cols="4" :items="[
        ['label' => 'Purchase Date', 'value' => $purchase->purchase_date->format('d M Y')],
        ['label' => 'Payment Status', 'value' => ucfirst($purchase->payment_status)],
        ['label' => 'Branch', 'value' => $branch?->name],
        ['label' => 'Reference', 'value' => $purchase->reference_number],
    ]" />

    <x-document.parties :from="[
        'title' => 'Purchased From (Supplier)',
        'name' => $supplier?->name,
        'lines' => [
            $supplier?->phone,
            $supplier?->email,
            $supplier?->address,
            $supplier?->contact_person ? 'Contact: ' . $supplier->contact_person : null,
        ],
    ]" :to="[
        'title' => 'Received By',
        'name' => $shop?->name,
        'lines' => [
            $branch?->name ? 'Branch: ' . $branch->name : null,
            $shop?->address,
            $purchase->created_by ? 'Received by: ' . $purchase->createdBy?->name : null,
        ],
    ]" />

    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th>Product / Description</th>
                <th class="center" style="width:10%">Qty</th>
                <th class="right" style="width:15%">Unit Cost</th>
                <th style="width:12%">Warranty</th>
                <th class="right" style="width:15%">Line Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($purchase->lineItems as $i => $line)
                <tr>
                    <td class="center muted">{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $line->variant?->product?->name }}</strong>
                        @if ($line->variant?->attributes_label)
                            — {{ $line->variant->attributes_label }}
                        @endif
                        <br><span class="muted">SKU: {{ $line->variant?->sku }}</span>
                    </td>
                    <td class="center">{{ $line->quantity }}</td>
                    <td class="right mono">৳{{ number_format($line->unit_cost, 2) }}</td>
                    <td style="font-size:7.5pt;color:#6B7280;">
                        @if ($line->manufacturer_warranty_months)
                            {{ $line->manufacturer_warranty_months }}mo mfr
                        @endif
                        @if ($line->shop_warranty_days)
                            · {{ $line->shop_warranty_days }}d shop
                        @endif
                    </td>
                    <td class="right mono doc-text-bold">৳{{ number_format($line->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="doc-totals">
        <div class="doc-totals-table">
            <div class="doc-totals-row grand">
                <span class="label">GRAND TOTAL</span>
                <span class="amount">৳{{ number_format($purchase->total_amount, 2) }}</span>
            </div>
        </div>
    </div>

    <div class="doc-amount-words">
        <strong>Amount:</strong> Taka {{ number_format($purchase->total_amount, 2) }} Only
    </div>

    @if ($purchase->notes)
        <div class="doc-notes"><span class="doc-notes-label">Notes</span>{{ $purchase->notes }}</div>
    @endif

    <x-document.signatures :signatories="$signatories" />

</x-document.layout>
