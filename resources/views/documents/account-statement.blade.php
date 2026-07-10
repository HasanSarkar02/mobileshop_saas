@php
    $shop = auth()->user()?->shop;
    $branch = $branchId ? \App\Models\Branch::find($branchId) : null;
    $stmt = $statement;

    $signatories = [
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => 'Reviewed By', 'name' => ''],
        ['title' => 'Authorized By', 'name' => ''],
    ];
@endphp

<x-document.layout title="Account Statement" :subtitle="$stmt->account->name" :shop="$shop" :branch="$branch" :exportPdfUrl="route('reports.account-statement.pdf', request()->all())">
    <x-document.report-header :title="'Account Statement'" :period="$periodLabel" :branch="$branch?->name ?? 'All Branches'" :filters="[
        ['label' => 'Account', 'value' => $stmt->account->name],
        ['label' => 'Provider', 'value' => ucfirst($stmt->account->provider ?? 'other')],
    ]" />

    {{-- Summary --}}
    <div class="doc-two-col" style="margin-bottom:4mm;">
        <div>
            <div class="doc-kv-row"><span class="doc-kv-label">Opening Balance</span><span
                    class="doc-kv-value">৳{{ number_format($stmt->opening_balance, 2) }}</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Total Inflow</span><span
                    class="doc-kv-value doc-text-green">+৳{{ number_format($stmt->period_debits, 2) }}</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Total Outflow</span><span
                    class="doc-kv-value doc-text-red">-৳{{ number_format($stmt->period_credits, 2) }}</span></div>
        </div>
        <div style="display:flex;align-items:center;justify-content:center;border:2pt solid #1e3a5f;padding:4mm;">
            <div style="text-align:center;">
                <div style="font-size:7pt;color:#6B7280;text-transform:uppercase;letter-spacing:0.05em;">Closing Balance
                </div>
                <div style="font-size:18pt;font-weight:700;color:#1e3a5f;font-family:var(--doc-mono);">
                    ৳{{ number_format($stmt->closing_balance, 2) }}
                </div>
            </div>
        </div>
    </div>

    <table class="doc-table">
        <thead>
            <tr>
                <th style="width:12%">Date</th>
                <th style="width:12%">Entry No.</th>
                <th style="width:36%">Description</th>
                <th style="width:13%" class="right">Inflow (৳)</th>
                <th style="width:13%" class="right">Outflow (৳)</th>
                <th style="width:14%" class="right">Balance (৳)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="subtotal-row">
                <td colspan="5" style="font-style:italic;">Opening Balance b/f</td>
                <td class="right mono">{{ number_format($stmt->opening_balance, 2) }}</td>
            </tr>
            @foreach ($stmt->lines as $line)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($line->entry_date)->format('d M Y') }}</td>
                    <td class="mono muted">{{ $line->entry_number }}</td>
                    <td>{{ $line->line_description ?: $line->entry_description }}</td>
                    <td class="right mono {{ $line->debit > 0 ? 'doc-text-green' : 'muted' }}">
                        {{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}
                    </td>
                    <td class="right mono {{ $line->credit > 0 ? 'doc-text-red' : 'muted' }}">
                        {{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}
                    </td>
                    <td class="right mono doc-text-bold {{ $line->running_balance < 0 ? 'doc-text-red' : '' }}">
                        {{ number_format($line->running_balance, 2) }}
                    </td>
                </tr>
            @endforeach
            <tr class="grand-total-row">
                <td colspan="3">Closing Balance c/f — {{ $stmt->date_range->to->format('d M Y') }}</td>
                <td class="right mono">{{ number_format($stmt->period_debits, 2) }}</td>
                <td class="right mono">{{ number_format($stmt->period_credits, 2) }}</td>
                <td class="right mono">{{ number_format($stmt->closing_balance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="doc-notes" style="margin-top:3mm;">
        <strong>Note:</strong> This statement reflects all accounting journal entries affecting the
        <strong>{{ $stmt->account->name }}</strong> account for the period {{ $periodLabel }}.
        Balances are derived from the double-entry General Ledger.
    </div>

    <x-document.signatures :signatories="$signatories" />

</x-document.layout>
