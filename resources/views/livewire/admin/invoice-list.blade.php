<div class="space-y-5">
    <h2 class="text-xl font-bold text-gray-900">Subscription Invoices</h2>

    @php $t = $this->totals; @endphp
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 border-0 bg-red-50">
            <div class="text-xs font-semibold text-red-500 uppercase mb-1">Pending (৳)</div>
            <div class="text-2xl font-bold text-red-700">৳{{ number_format($t->pending, 0) }}</div>
        </div>
        <div class="card p-4 border-0 bg-green-50">
            <div class="text-xs font-semibold text-green-500 uppercase mb-1">Collected (৳)</div>
            <div class="text-2xl font-bold text-green-700">৳{{ number_format($t->paid, 0) }}</div>
        </div>
        <div class="card p-4 border-0 bg-amber-50">
            <div class="text-xs font-semibold text-amber-500 uppercase mb-1">Overdue Count</div>
            <div class="text-2xl font-bold text-amber-700">{{ $t->overdue }}</div>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search shop…"
            class="input max-w-xs text-sm">
        @foreach (['' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'waived' => 'Waived'] as $val => $label)
            <button wire:click="$set('statusFilter', '{{ $val }}')"
                class="px-3 py-1.5 rounded-lg text-xs font-medium
                    {{ $statusFilter === $val ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-600' }}">
                {{ $label }}
            </button>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Invoice #</th>
                        <th class="table-th">Shop</th>
                        <th class="table-th">Plan</th>
                        <th class="table-th text-right">Amount</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Due Date</th>
                        <th class="table-th">Paid On</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->invoices as $inv)
                        @php
                            $overdue = $inv->status === 'pending' && $inv->due_date->isPast();
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $overdue ? 'bg-red-50/40' : '' }}"
                            wire:key="inv-{{ $inv->id }}">
                            <td class="table-td font-mono text-indigo-600 text-sm">{{ $inv->invoice_number }}</td>
                            <td class="table-td font-medium text-gray-900">{{ $inv->shop?->name }}</td>
                            <td class="table-td text-gray-500 text-sm">{{ $inv->subscription?->plan?->name }}</td>
                            <td class="table-td text-right font-bold">৳{{ number_format($inv->amount, 0) }}</td>
                            <td class="table-td">
                                @php
                                    $bc = match ($inv->status) {
                                        'paid' => 'badge-green',
                                        'pending' => $overdue ? 'badge-red' : 'badge-yellow',
                                        'waived' => 'badge-gray',
                                        default => 'badge-gray',
                                    };
                                @endphp
                                <span class="badge {{ $bc }} text-xs">
                                    {{ ucfirst($inv->status) }}
                                    @if ($overdue)
                                        (Overdue)
                                    @endif
                                </span>
                            </td>
                            <td
                                class="table-td text-sm {{ $overdue ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                {{ $inv->due_date->format('d M Y') }}
                            </td>
                            <td class="table-td text-sm text-green-600">
                                {{ $inv->paid_at?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="table-td">
                                @if ($inv->status === 'pending')
                                    <div class="flex gap-3">
                                        <button wire:click="markInvoicePaid({{ $inv->id }})"
                                            wire:confirm="Mark invoice as paid?"
                                            class="text-xs text-green-600 hover:underline font-medium">
                                            ✓ Paid
                                        </button>
                                        <button wire:click="waive({{ $inv->id }})"
                                            wire:confirm="Waive this invoice?"
                                            class="text-xs text-gray-400 hover:underline">
                                            Waive
                                        </button>
                                    </div>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="table-td text-center text-gray-400 py-10">No invoices.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($this->invoices->hasPages())
            <div class="px-4 py-3 border-t">{{ $this->invoices->links() }}</div>
        @endif
    </div>
</div>
