<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Service & Repair Report</h2>
    </div>

    <x-report-filter :period="$period" :dateFrom="$dateFrom" :dateTo="$dateTo" :branchId="$branchId" :branches="$branches" />

    @php $stats = $this->stats; @endphp

    {{-- KPIs --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ([['label' => 'Active Tickets', 'value' => number_format($stats->active), 'color' => 'indigo'], ['label' => 'Ready for Pickup', 'value' => number_format($stats->ready_for_pickup), 'color' => 'green'], ['label' => 'Total Amount Due', 'value' => '৳' . number_format($stats->total_due, 0), 'color' => 'red'], ['label' => 'Period Revenue', 'value' => '৳' . number_format($this->periodRevenue, 0), 'color' => 'amber']] as $kpi)
            <div class="card p-5 border-0 bg-{{ $kpi['color'] }}-50">
                <div class="text-xs font-semibold text-{{ $kpi['color'] }}-500 uppercase tracking-wider mb-1">
                    {{ $kpi['label'] }}</div>
                <div class="text-2xl font-bold text-{{ $kpi['color'] }}-800">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Tabs --}}
    <div class="card overflow-hidden">
        <nav class="flex border-b border-gray-200">
            @foreach ([['key' => 'overview', 'label' => 'Open Tickets'], ['key' => 'technicians', 'label' => 'Technician Performance'], ['key' => 'status', 'label' => 'Status Breakdown']] as $tab)
                <button wire:click="$set('activeView', '{{ $tab['key'] }}')"
                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors
                        {{ $activeView === $tab['key'] ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>

        {{-- Open Tickets --}}
        <div wire:show="activeView === 'overview'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Ticket</th>
                        <th class="table-th">Customer</th>
                        <th class="table-th">Device</th>
                        <th class="table-th">Technician</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Charge</th>
                        <th class="table-th text-right">Due</th>
                        <th class="table-th">Age</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->openTickets as $t)
                        @php $t = (object) $t; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <a href="{{ route('service.show', $t->id) }}" wire:navigate
                                    class="font-mono font-semibold text-indigo-600 hover:underline text-sm">
                                    {{ $t->ticket_number }}
                                </a>
                            </td>
                            <td class="table-td">
                                <div class="text-sm font-medium text-gray-900">{{ $t->customer_name }}</div>
                                <div class="text-xs text-gray-400">{{ $t->customer_phone }}</div>
                            </td>
                            <td class="table-td text-sm text-gray-700">{{ $t->device_model ?? '—' }}</td>
                            <td class="table-td text-xs text-gray-500">{{ $t->technician_name ?? 'Unassigned' }}</td>
                            <td class="table-td">
                                @php
                                    $statusColors = [
                                        'received' => 'badge-gray',
                                        'diagnosing' => 'badge-yellow',
                                        'in_repair' => 'badge-blue',
                                        'ready' => 'badge-green',
                                    ];
                                @endphp
                                <span class="badge {{ $statusColors[$t->status] ?? 'badge-gray' }} text-xs">
                                    {{ ucfirst(str_replace('_', ' ', $t->status)) }}
                                </span>
                            </td>
                            <td class="table-td text-right font-semibold">৳{{ number_format($t->total_charge, 0) }}
                            </td>
                            <td
                                class="table-td text-right {{ $t->amount_due > 0 ? 'font-bold text-red-600' : 'text-gray-300' }}">
                                {{ $t->amount_due > 0 ? '৳' . number_format($t->amount_due, 0) : 'Paid' }}
                            </td>
                            <td class="table-td text-xs">
                                @php $days = \Carbon\Carbon::parse($t->received_at)->diffInDays(); @endphp
                                <span class="{{ $days > 7 ? 'text-red-500 font-semibold' : 'text-gray-500' }}">
                                    {{ $days }}d
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="table-td text-center text-gray-400 py-8">
                                🎉 No open tickets!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Technician Performance --}}
        <div wire:show="activeView === 'technicians'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Technician</th>
                        <th class="table-th text-right">Tickets Completed</th>
                        <th class="table-th text-right">Revenue Generated</th>
                        <th class="table-th text-right">Avg. Revenue/Ticket</th>
                        <th class="table-th text-right">Avg. Turnaround</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->technicianPerformance as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-semibold text-gray-900">{{ $row->name }}</td>
                            <td class="table-td text-right">{{ $row->tickets_completed }}</td>
                            <td class="table-td text-right font-bold text-green-600">
                                ৳{{ number_format($row->total_revenue, 0) }}</td>
                            <td class="table-td text-right text-gray-600">
                                ৳{{ $row->tickets_completed > 0 ? number_format($row->total_revenue / $row->tickets_completed, 0) : 0 }}
                            </td>
                            <td class="table-td text-right text-gray-500">
                                {{ number_format($row->avg_turnaround_hours, 1) }}h
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-td text-center text-gray-400 py-8">No delivered tickets in
                                this period.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Status Breakdown --}}
        <div wire:show="activeView === 'status'" class="p-5">
            <div class="space-y-3">
                @php $total = $this->statusBreakdown->sum('count'); @endphp
                @foreach ($this->statusBreakdown as $row)
                    @php $pct = $total > 0 ? round($row->count / $total * 100, 1) : 0; @endphp
                    <div class="flex items-center gap-4">
                        <span class="w-28 text-sm font-medium text-gray-700 capitalize shrink-0">
                            {{ str_replace('_', ' ', $row->status) }}
                        </span>
                        <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                            <div class="bg-indigo-500 h-3 rounded-full" style="width:{{ $pct }}%"></div>
                        </div>
                        <span class="text-sm text-gray-600 w-8 text-right">{{ $row->count }}</span>
                        <span class="text-xs text-gray-400 w-12 text-right">{{ $pct }}%</span>
                        <span class="text-sm font-semibold text-gray-900 w-28 text-right">
                            ৳{{ number_format($row->revenue, 0) }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
