<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Reference or supplier…"
            class="input max-w-xs">
        <div class="flex items-center gap-2">
            <input wire:model.live="dateFrom" type="date" class="input w-auto" title="From">
            <span class="text-gray-400 text-sm">to</span>
            <input wire:model.live="dateTo" type="date" class="input w-auto" title="To">
        </div>
        <a href="{{ route('purchases.create') }}" wire:navigate class="btn-primary sm:ml-auto whitespace-nowrap">+ New
            Purchase</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Reference</th>
                        <th class="table-th">Supplier</th>
                        <th class="table-th">Branch</th>
                        <th class="table-th">Date</th>
                        <th class="table-th">Total</th>
                        <th class="table-th">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($purchases as $purchase)
                        <tr class="hover:bg-gray-50 cursor-pointer"
                            wire:click="$navigate('{{ route('purchases.show', $purchase) }}')">
                            <td class="table-td font-mono text-sm font-medium text-indigo-700">
                                {{ $purchase->reference_number }}</td>
                            <td class="table-td font-medium">{{ $purchase->supplier->name }}</td>
                            <td class="table-td text-gray-500">{{ $purchase->branch->name }}</td>
                            <td class="table-td text-gray-500">{{ $purchase->purchase_date->format('d M Y') }}</td>
                            <td class="table-td font-semibold">৳{{ number_format($purchase->total_amount, 2) }}</td>
                            <td class="table-td">
                                <span
                                    class="{{ $purchase->payment_status === 'paid' ? 'badge-green' : 'badge-yellow' }} badge">
                                    {{ ucfirst($purchase->payment_status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-td text-center text-gray-400 py-12">
                                No purchases yet. <a href="{{ route('purchases.create') }}" wire:navigate
                                    class="text-indigo-600 hover:underline">Create one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($purchases->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $purchases->links() }}</div>
        @endif
    </div>
</div>
