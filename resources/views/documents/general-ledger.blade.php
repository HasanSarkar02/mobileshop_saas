@php
    $shop = auth()->user()?->shop;
    $branch = $branchId ? \App\Models\Branch::find($branchId) : null;

    $signatories = [
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => 'Reviewed By', 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp

<x-document.layout title="General Ledger" :subtitle="'[' . $account->code . '] ' . $account->name . ' — ' . $fromLabel . ' to ' . $toLabel" :shop="$shop">

    {{-- Summary --}}
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:12px;">
        @foreach ([['label' => 'Opening Balance', 'value' => $opening, 'color' => '#64748b'], ['label' => 'Period Debits', 'value' => $totalDr, 'color' => '#16a34a'], ['label' => 'Closing Balance', 'value' => $closing, 'color' => '#4f46e5']] as $card)
            <div style="border:1px solid #e2e8f0;border-radius:6px;padding:8px 10px;">
                <div style="font-size:7pt;color:#94a3b8;text-transform:uppercase;font-weight:600;">{{ $card['label'] }}
                </div>
                <div style="font-size:13pt;font-weight:700;color:{{ $card['color'] }};margin-top:2px;">
                    ৳{{ number_format($card['value'], 2) }}
                </div>
            </div>
        @endforeach
    </div>

    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:10%">Date</th>
                <th style="width:12%">Entry No.</th>
                <th>Description</th>
                <th class="right" style="width:13%">Debit (৳)</th>
                <th class="right" style="width:13%">Credit (৳)</th>
                <th class="right" style="width:13%">Balance (৳)</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background:#f8fafc;">
                <td colspan="5" style="font-weight:600;color:#64748b;">Opening Balance b/f</td>
                <td class="right doc-text-bold">{{ number_format($opening, 2) }}</td>
            </tr>
            @forelse($lines as $line)
                <tr>
                    <td class="muted" style="white-space:nowrap;font-size:7.5pt;">
                        {{ \Carbon\Carbon::parse($line->entry_date)->format('d M Y') }}
                    </td>
                    <td class="mono muted" style="font-size:7.5pt;">{{ $line->entry_number }}</td>
                    <td style="font-size:8pt;">{{ $line->line_desc ?: $line->description }}</td>
                    <td class="right {{ $line->debit > 0 ? 'doc-text-bold' : 'muted' }}">
                        {{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}
                    </td>
                    <td class="right {{ $line->credit > 0 ? 'doc-text-bold doc-text-red' : 'muted' }}">
                        {{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}
                    </td>
                    <td class="right doc-text-bold {{ $line->balance < 0 ? 'doc-text-red' : '' }}">
                        {{ number_format($line->balance, 2) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;color:#94a3b8;padding:20px;">
                        No transactions in this period.
                    </td>
                </tr>
            @endforelse
            <tr style="background:#eff6ff;font-weight:700;border-top:2px solid #4f46e5;">
                <td colspan="3" style="color:#1e40af;">Closing Balance c/f</td>
                <td class="right" style="color:#16a34a;">{{ number_format($totalDr, 2) }}</td>
                <td class="right" style="color:#dc2626;">({{ number_format($totalCr, 2) }})</td>
                <td class="right" style="color:#4f46e5;font-size:10pt;">{{ number_format($closing, 2) }}</td>
            </tr>
        </tbody>
    </table>
    <x-document.signatures :signatories="$signatories" />
</x-document.layout>
