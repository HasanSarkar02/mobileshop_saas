@php
    $shop = auth()->user()?->shop;
    $signatories = [
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp

<x-document.layout title="Expense Analysis Report" :subtitle="$periodLabel" :shop="$shop">
    <x-document.report-header :title="'Expense Analysis Report'" :period="$periodLabel" :branch="'All Branches'" />

    {{-- KPI --}}
    <div class="doc-two-col" style="margin-bottom:4mm;">
        <div>
            <div class="doc-kv-row"><span class="doc-kv-label">Total Entries</span><span
                    class="doc-kv-value">{{ number_format($aggregate->count) }}</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Total Amount</span><span
                    class="doc-kv-value doc-text-bold doc-text-red">৳{{ number_format($aggregate->total, 2) }}</span>
            </div>
        </div>
    </div>

    <div class="doc-section-title">By Category</div>
    <table class="doc-table">
        <thead>
            <tr>
                <th>Category</th>
                <th>Parent</th>
                <th class="right">Count</th>
                <th class="right">Amount (৳)</th>
                <th class="right">% Share</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($byCategory as $row)
                @php $pct = $aggregate->total > 0 ? round($row->total / $aggregate->total * 100, 1) : 0; @endphp
                <tr>
                    <td>{{ $row->category }}</td>
                    <td class="muted">{{ $row->parent_category !== $row->category ? $row->parent_category : '—' }}</td>
                    <td class="right">{{ $row->count }}</td>
                    <td class="right mono doc-text-red">{{ number_format($row->total, 2) }}</td>
                    <td class="right">{{ $pct }}%</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="grand-total-row">
                <td colspan="3">TOTAL</td>
                <td class="right mono">{{ number_format($aggregate->total, 2) }}</td>
                <td class="right">100%</td>
            </tr>
        </tfoot>
    </table>

    @if ($byBranch->isNotEmpty())
        <div class="doc-section-title" style="margin-top:4mm;">By Branch</div>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Branch</th>
                    <th class="right">Count</th>
                    <th class="right">Amount (৳)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($byBranch as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td class="right">{{ $row->count }}</td>
                        <td class="right mono">{{ number_format($row->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <x-document.signatures :signatories="$signatories" />
</x-document.layout>
