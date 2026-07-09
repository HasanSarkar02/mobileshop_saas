<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">
                Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }},
                {{ auth()->user()->name }}!
            </h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ now()->format('l, d F Y') }}</p>
        </div>
        <a href="{{ route('pos') }}" wire:navigate class="btn-primary">
            🛒 Open POS
        </a>
    </div>

    {{-- My Sales Today --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="card p-4 border-0 bg-indigo-50">
            <div class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-1">My Sales Today</div>
            <div class="text-2xl font-bold text-indigo-800">{{ $employeeSales['today_count'] }}</div>
            <div class="text-sm text-indigo-500 mt-0.5">orders</div>
        </div>
        <div class="card p-4 border-0 bg-green-50">
            <div class="text-xs font-semibold text-green-500 uppercase tracking-wider mb-1">Today's Revenue</div>
            <div class="text-2xl font-bold text-green-800">৳{{ number_format($employeeSales['today_revenue'], 0) }}
            </div>
        </div>
        <div class="card p-4 border-0 bg-blue-50">
            <div class="text-xs font-semibold text-blue-500 uppercase tracking-wider mb-1">My Sales This Month</div>
            <div class="text-2xl font-bold text-blue-800">{{ $employeeSales['month_count'] }}</div>
        </div>
        <div class="card p-4 border-0 bg-teal-50">
            <div class="text-xs font-semibold text-teal-500 uppercase tracking-wider mb-1">Month Revenue</div>
            <div class="text-2xl font-bold text-teal-800">৳{{ number_format($employeeSales['month_revenue'], 0) }}</div>
        </div>
    </div>

    {{-- Pending Approval Notification --}}
    @if ($pendingExpenses > 0)
        <div class="card p-4 bg-amber-50 border-amber-200">
            <p class="text-sm text-amber-800 font-medium">
                ⏳ You have {{ $pendingExpenses }} expense(s) awaiting owner approval.
                <a href="{{ route('expenses.index') }}" wire:navigate class="underline ml-1">View →</a>
            </p>
        </div>
    @endif

    {{-- My Service Tickets --}}
    @if ($myTickets->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm">My Service Tickets</h3>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Ticket</th>
                        <th class="table-th">Customer</th>
                        <th class="table-th">Device</th>
                        <th class="table-th">Status</th>
                        <th class="table-th text-right">Due</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($myTickets as $ticket)
                        <tr class="hover:bg-gray-50 cursor-pointer"
                            wire:click="$navigate('{{ route('service.show', $ticket->id) }}')">
                            <td class="table-td font-mono text-indigo-600 text-sm font-semibold">
                                {{ $ticket->ticket_number }}
                            </td>
                            <td class="table-td text-sm text-gray-900">{{ $ticket->customer_name }}</td>
                            <td class="table-td text-sm text-gray-600">{{ $ticket->device_model ?? '—' }}</td>
                            <td class="table-td">
                                @php
                                    $sc = [
                                        'received' => 'badge-gray',
                                        'diagnosing' => 'badge-yellow',
                                        'in_repair' => 'badge-blue',
                                        'ready' => 'badge-green',
                                    ];
                                @endphp
                                <span class="badge {{ $sc[$ticket->status->value] ?? 'badge-gray' }} text-xs">
                                    {{ $ticket->status->label() }}
                                </span>
                            </td>
                            <td
                                class="table-td text-right {{ $ticket->amount_due > 0 ? 'font-bold text-red-600' : 'text-gray-300' }}">
                                {{ $ticket->amount_due > 0 ? '৳' . number_format($ticket->amount_due, 0) : 'Paid' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="card p-5 text-center text-gray-400 text-sm">
            No active service tickets assigned to you.
        </div>
    @endif

    {{-- Quick Links --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        @foreach ([['route' => 'pos', 'label' => '🛒 New Sale', 'can' => 'sales.create'], ['route' => 'sales.index', 'label' => '📋 Sales History', 'can' => 'sales.view'], ['route' => 'service.index', 'label' => '🔧 Service Tickets', 'can' => 'service.view'], ['route' => 'expenses.create', 'label' => '🧾 Add Expense', 'can' => 'expenses.create']] as $link)
            @can($link['can'])
                <a href="{{ route($link['route']) }}" wire:navigate
                    class="card p-4 text-center text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-indigo-700 transition-colors">
                    {{ $link['label'] }}
                </a>
            @endcan
        @endforeach
    </div>
</div>
