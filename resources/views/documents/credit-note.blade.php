@php
    $shop = $creditNote->shop;
    $branch = $creditNote->branch;
    $sale = $creditNote->originalSale;
    $customer = $creditNote->customer;

    $signatories = [
        ['title' => 'Processed By', 'name' => ''],
        ['title' => 'Customer Received', 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp

<x-document.layout title="Credit Note" :subtitle="'Against Invoice: ' . $sale?->sale_number" :docNumber="$creditNote->credit_note_number" :shop="$shop" :branch="$branch"
    :exportPdfUrl="route('documents.credit-note.pdf', $creditNote)">
    <x-document.meta :cols="4" :items="[
        ['label' => 'Credit Note Date', 'value' => $creditNote->created_at->format('d M Y')],
        ['label' => 'Original Invoice', 'value' => $sale?->sale_number],
        ['label' => 'Refund Method', 'value' => $creditNote->refund_method->label()],
        ['label' => 'Status', 'value' => $creditNote->status->label()],
    ]" />

    <x-document.parties :to="[
        'title' => 'Customer',
        'name' => $customer?->name ?? 'Walk-in',
        'lines' => [$customer?->phone, $customer?->address],
    ]" :extra="[
        'title' => 'Return Reason',
        'lines' => [$creditNote->reason, $creditNote->notes],
    ]" />

    <div class="doc-section-title">Returned Items</div>
    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:4%">#</th>
                <th>Item</th>
                <th class="center" style="width:10%">Qty</th>
                <th class="right" style="width:18%">Unit Price</th>
                <th style="width:15%">Condition</th>
                <th style="width:10%" class="center">Restocked</th>
                <th class="right" style="width:18%">Refund Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($creditNote->items as $i => $item)
                <tr>
                    <td class="center muted">{{ $i + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if ($item->variant_label)
                            <br><span class="muted">{{ $item->variant_label }}</span>
                        @endif
                        @if ($item->serial_number)
                            <br><span class="mono">IMEI: {{ $item->serial_number }}</span>
                        @endif
                    </td>
                    <td class="center">{{ $item->quantity }}</td>
                    <td class="right mono">৳{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->condition->label() }}</td>
                    <td class="center">{{ $item->restock ? '✓ Yes' : '✗ No' }}</td>
                    <td class="right mono doc-text-red">(৳{{ number_format($item->line_total, 2) }})</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="doc-totals">
        <div class="doc-totals-table">
            <div class="doc-totals-row grand">
                <span class="label">TOTAL REFUND</span>
                <span class="amount">৳{{ number_format($creditNote->refund_amount, 2) }}</span>
            </div>
        </div>
    </div>

    <x-document.signatures :signatories="$signatories" />

</x-document.layout>
