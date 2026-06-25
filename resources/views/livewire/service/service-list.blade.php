<div class="space-y-4">

    {{-- Stats --}}
    @php $s = $this->stats; @endphp
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @foreach ([['label' => 'Active Tickets', 'value' => $s['active'], 'color' => 'bg-indigo-50 text-indigo-700'], ['label' => 'Ready Pickup', 'value' => $s['ready'], 'color' => 'bg-green-50 text-green-700'], ['label' => 'Amount Due', 'value' => '৳' . number_format($s['due'], 0), 'color' => 'bg-red-50 text-red-700'], ['label' => 'Month Revenue', 'value' => '৳' . number_format($s['this_month_revenue'], 0), 'color' => 'bg-amber-50 text-amber-700']] as $card)
            <div class="card p-4 border-0 {{ $card['color'] }}">
                <div class="text-xl font-bold">{{ $card['value'] }}</div>
                <div class="text-xs font-medium mt-0.5 opacity-75">{{ $card['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Ticket no., customer, IMEI, model…"
            class="input max-w-xs">
        <select wire:model.live="status" class="input w-auto">
            <option value="">All statuses</option>
            @foreach (\App\Enums\ServiceTicketStatus::cases() as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
        @can('service.manage')
            <a href="{{ route('service.create') }}" wire:navigate class="btn-primary sm:ml-auto whitespace-nowrap">
                + New Ticket
            </a>
        @endcan
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Ticket</th>
                        <th class="table-th">Customer</th>
                        <th class="table-th">Device</th>
                        <th class="table-th">Problem</th>
                        <th class="table-th">Technician</th>
                        <th class="table-th text-right">Charge</th>
                        <th class="table-th text-right">Due</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($tickets as $ticket)
                        <tr class="hover:bg-gray-50 cursor-pointer">
                            <td class="table-td font-mono font-semibold text-indigo-600 text-sm">
                                <a href="{{ route('service.show', $ticket) }}" wire:navigate>
                                    {{ $ticket->ticket_number }}
                                </a>
                                @if ($ticket->is_warranty_service)
                                    <span class="badge badge-blue text-xs block mt-0.5">Warranty</span>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="font-medium text-sm text-gray-900">{{ $ticket->customer_name }}</div>
                                <div class="text-xs text-gray-400">{{ $ticket->customer_phone }}</div>
                            </td>
                            <td class="table-td text-sm">
                                <div class="text-gray-700">{{ $ticket->device_model ?? '—' }}</div>
                                @if ($ticket->device_imei)
                                    <div class="font-mono text-xs text-indigo-400">{{ $ticket->device_imei }}</div>
                                @endif
                            </td>
                            <td class="table-td text-sm text-gray-600 max-w-[180px] truncate">
                                {{ $ticket->problem_description }}
                            </td>
                            <td class="table-td text-xs text-gray-500">
                                {{ $ticket->technician?->name ?? '—' }}
                            </td>
                            <td class="table-td text-right font-semibold">
                                ৳{{ number_format($ticket->total_charge, 0) }}
                            </td>
                            <td
                                class="table-td text-right font-bold {{ $ticket->amount_due > 0 ? 'text-red-600' : 'text-green-600' }}">
                                @if ($ticket->amount_due > 0)
                                    ৳{{ number_format($ticket->amount_due, 0) }}
                                @else
                                    Paid ✓
                                @endif
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $ticket->status->badgeClass() }}">
                                    {{ $ticket->status->label() }}
                                </span>
                            </td>
                            <td class="table-td text-xs text-gray-400">
                                {{ $ticket->received_at?->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-td text-center text-gray-400 py-12">
                                No service tickets yet.
                                <a href="{{ route('service.create') }}" wire:navigate
                                    class="text-indigo-600 hover:underline">Create one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($tickets->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>
