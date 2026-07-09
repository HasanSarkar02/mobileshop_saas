@php
    $shop     = auth()->user()?->shop;
    $supplier = $statement['supplier'];
    $ledger   = $statement['ledger'];
    $aging    = $statement['aging'];
@endphp

<x-document.layout
    title="Supplier Statement"
    :subtitle="$supplier->name"
    :shop="$shop"
    :exportPdfUrl="route('documents.supplier-statement.pdf', ['supplier' => $supplier->id, 'from' => $statement['from'], 'to' => $statement['to']])"
>
    <x-document.meta :cols="4" :items="[
        ['label' => 'Supplier',       'value' => $supplier->name],
        ['label' => 'Phone',          'value' => $supplier->phone ?? '—'],
        ['label' => 'Period',         'value' => $statement['period_label']],
        ['label' => 'Generated',      'value' => now()->format('d M Y H:i')],
    ]" />

    {{-- Aging Summary --}}
    <div class="doc-two-col" style="margin-bottom:4mm;">
        <div>
            <div class="doc-section-title">Outstanding Balance Summary</div>
            @foreach([
                ['label' => 'Current (Not Yet Due)',  'val' => $aging['current']],
                ['label' => '1–30 Days Overdue',      'val' => $aging['1_30']],
                ['label' => '31–60 Days Overdue',     'val' => $aging['31_60']],
                ['label' => '61–90 Days Overdue',     'val' => $aging['61_90']],
                ['label' => 'Over 90 Days',           'val' => $aging['over_90']],
            ] as $row)
                <div class="doc-kv-row">
                    <span class="doc-kv-label">{{ $row['label'] }}</span>
                    <span class="doc-kv-value {{ $row['val'] > 0 ? 'doc-text-red' : '' }}">
                        ৳{{ number_format($row['val'], 2) }}
                    </span>
                </div>
            @endforeach
            <div class="doc-kv-row" style="border-top:1.5pt solid #1e3a5f;margin-top:1mm;padding-top:1mm;">
                <span class="doc-kv-label doc-text-bold">TOTAL OUTSTANDING</span>
                <span class="doc-kv-value doc-text-bold doc-text-red">
                    ৳{{ number_format($statement['closing_balance'], 2) }}
                </span>
            </div>
        </div>
        <div>
            @if($supplier->bank_name || $supplier->bank_account_number)
                <div class="doc-section-title">Supplier Bank Details</div>
                @foreach([
                    ['label' => 'Bank',    'value' => $supplier->bank_name],
                    ['label' => 'Account', 'value' => $supplier->bank_account_number],
                    ['label' => 'Branch',  'value' => $supplier->bank_branch_name],
                    ['label' => 'Routing', 'value' => $supplier->bank_routing_number],
                    ['label' => 'Terms',   'value' => $supplier->payment_terms],
                ] as $row)
                    @if($row['value'])
                        <div class="doc-kv-row">
                            <span class="doc-kv-label">{{ $row['label'] }}</span>
                            <span class="doc-kv-value">{{ $row['value'] }}</span>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>

    {{-- Transaction Ledger --}}
    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:12%">Date</th>
                <th style="width:12%">Type</th>
                <th style="width:22%">Reference</th>
                <th class="right" style="width:18%">Purchased (৳)</th>
                <th class="right" style="width:18%">Paid/Returned (৳)</th>
                <th class="right" style="width:18%">Balance (৳)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ledger as $row)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row->txn_date)->format('d M Y') }}</td>
                    <td>{{ $row->txn_type }}</td>
                    <td class="mono muted">{{ $row->reference ?? '—' }}</td>
                    <td class="right mono {{ $row->debit > 0 ? 'doc-text-red' : 'muted' }}">
                        {{ $row->debit > 0 ? number_format($row->debit, 2) : '—' }}
                    </td>
                    <td class="right mono {{ $row->credit > 0 ? 'doc-text-green' : 'muted' }}">
                        {{ $row->credit > 0 ? number_format($row->credit, 2) : '—' }}
                    </td>
                    <td class="right mono doc-text-bold {{ $row->running_balance > 0 ? 'doc-text-red' : '' }}">
                        {{ number_format($row->running_balance, 2) }}
                    </td>
                </tr>
            @endforeach
            <tr class="grand-total-row">
                <td colspan="5">Closing Balance — {{ now()->format('d M Y') }}</td>
                <td class="right mono">{{ number_format($statement['closing_balance'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="doc-notes" style="margin-top:3mm;">
        This statement shows all purchase transactions, payments made, and purchase returns
        for <strong>{{ $supplier->name }}</strong> for the period {{ $statement['period_label'] }}.
        Please contact us if you find any discrepancies.
    </div>

    <x-document.signatures :signatories="[
        ['title' => 'Prepared By',    'name' => auth()->user()?->name ?? ''],
        ['title' => 'Authorized By',  'name' => ''],
        ['title' => "Supplier's Acknowledgment", 'name' => ''],
    ]" />

</x-document.layout>