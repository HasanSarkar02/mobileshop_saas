@php $shop = auth()->user()?->shop; @endphp

<x-document.layout title="Service Report" :subtitle="$periodLabel" :shop="$shop">
    <x-document.report-header :title="'Service & Repair Report'" :period="$periodLabel" :branch="'All Branches'" />

    {{-- KPIs --}}
    <div class="doc-two-col" style="margin-bottom:4mm;">
        <div>
            <div class="doc-kv-row"><span class="doc-kv-label">Active Tickets</span><span
                    class="doc-kv-value">{{ $stats->active }}</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Ready for Pickup</span><span
                    class="doc-kv-value">{{ $stats->ready_for_pickup }}</span></div>
            <div class="doc-kv-row"><span class="doc-kv-label">Outstanding Due</span><span
                    class="doc-kv-value doc-text-red">৳{{ number_format($stats->total_due, 2) }}</span></div>
        </div>
        <div>
            <div class="doc-kv-row"><span class="doc-kv-label">Period Revenue</span><span
                    class="doc-kv-value doc-text-bold">৳{{ number_format($periodRevenue, 2) }}</span></div>
        </div>
    </div>

    {{-- Open Tickets --}}
    <div class="doc-section-title">Open Tickets</div>
    <table class="doc-table">
        <thead>
            <tr>
                <th>Ticket</th>
                <th>Customer</th>
                <th>Device</th>
                <th>Technician</th>
                <th>Status</th>
                <th class="right">Charge (৳)</th>
                <th class="right">Due (৳)</th>
                <th>Age</th>
            </tr>
        </thead>
        <tbody>
            @forelse($openTickets as $t)
                @php $t = (object) $t; @endphp
                <tr>
                    <td class="mono">{{ $t->ticket_number }}</td>
                    <td>{{ $t->customer_name }}</td>
                    <td>{{ $t->device_model ?? '—' }}</td>
                    <td class="muted">{{ $t->technician_name ?? 'Unassigned' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->status->value)) }}</td>
                    <td class="right mono">{{ number_format($t->total_charge, 2) }}</td>
                    <td class="right mono {{ $t->amount_due > 0 ? 'doc-text-red' : '' }}">
                        {{ $t->amount_due > 0 ? number_format($t->amount_due, 2) : 'Paid' }}
                    </td>
                    <td>{{ \Carbon\Carbon::parse($t->received_at)->diffInDays() }}d</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="center muted">No open tickets.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Technician Performance --}}
    @if ($techPerf->isNotEmpty())
        <div class="doc-section-title" style="margin-top:4mm;">Technician Performance</div>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>Technician</th>
                    <th class="right">Completed</th>
                    <th class="right">Revenue (৳)</th>
                    <th class="right">Avg/Ticket (৳)</th>
                    <th class="right">Avg Turnaround</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($techPerf as $row)
                    <tr>
                        <td>{{ $row->name }}</td>
                        <td class="right">{{ $row->tickets_completed }}</td>
                        <td class="right mono">{{ number_format($row->total_revenue, 2) }}</td>
                        <td class="right mono">
                            {{ $row->tickets_completed > 0 ? number_format($row->total_revenue / $row->tickets_completed, 2) : '—' }}
                        </td>
                        <td class="right">{{ number_format($row->avg_turnaround_hours, 1) }}h</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <x-document.signatures :signatories="[
        ['title' => 'Prepared By', 'name' => auth()->user()?->name ?? ''],
        ['title' => 'Authorized By', 'name' => ''],
    ]" />
</x-document.layout>
