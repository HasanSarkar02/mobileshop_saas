<div class="space-y-4">
    {{-- Stats --}}
    @php $s = $this->stats; @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
        <div class="card p-4 border-0 bg-indigo-50">
            <div class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-1">Total Suppliers</div>
            <div class="text-2xl font-bold text-indigo-700">{{ number_format($s->total_suppliers) }}</div>
        </div>
        <div class="card p-4 border-0 bg-red-50">
            <div class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Total Payable</div>
            <div class="text-2xl font-bold text-red-700">৳{{ number_format($s->total_payable, 0) }}</div>
        </div>
        <div class="card p-4 border-0 bg-amber-50">
            <div class="text-xs font-semibold text-amber-500 uppercase tracking-wider mb-1">Suppliers With Due</div>
            <div class="text-2xl font-bold text-amber-700">{{ number_format($s->suppliers_with_due) }}</div>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search suppliers…"
            class="input max-w-xs">
        <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
            <input wire:model.live="dueOnly" type="checkbox" value="1"
                class="rounded border-gray-300 text-indigo-600">
            Only show suppliers with due
        </label>
        <a href="{{ route('suppliers.create') }}" wire:navigate class="btn-primary sm:ml-auto">+ Add Supplier</a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Supplier</th>
                        <th class="table-th">Contact</th>
                        <th class="table-th">Purchases</th>
                        <th class="table-th text-right">Payable</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <a href="{{ route('suppliers.show', $supplier) }}" wire:navigate
                                    class="font-semibold text-indigo-600 hover:underline">
                                    {{ $supplier->name }}
                                </a>
                                @if ($supplier->contact_person)
                                    <div class="text-xs text-gray-400">{{ $supplier->contact_person }}</div>
                                @endif
                                @if ($supplier->address)
                                    <div class="text-xs text-gray-400">{{ $supplier->address }}</div>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="text-gray-900">{{ $supplier->phone ?? '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $supplier->email ?? '' }}</div>
                            </td>
                            <td class="table-td">
                                <span class="badge badge-gray">{{ $supplier->purchases_count }}</span>
                            </td>
                            <td class="table-td text-right">
                                @if ((float) $supplier->current_balance > 0)
                                    <span class="font-bold text-red-600">
                                        ৳{{ number_format($supplier->current_balance, 2) }}
                                    </span>
                                @else
                                    <span class="badge badge-green text-xs">Cleared</span>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('suppliers.show', $supplier) }}" wire:navigate
                                        class="text-xs text-indigo-600 hover:underline font-medium">Profile</a>
                                    <a href="{{ route('suppliers.edit', $supplier) }}" wire:navigate
                                        class="text-xs text-gray-500 hover:underline font-medium">Edit</a>
                                    @if ($supplier->purchases_count === 0)
                                        <button wire:click="delete({{ $supplier->id }})"
                                            wire:confirm="Delete {{ $supplier->name }}?"
                                            class="text-xs text-red-600 hover:underline font-medium">Delete</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-td text-center text-gray-400 py-12">
                                No suppliers yet. <a href="{{ route('suppliers.create') }}" wire:navigate
                                    class="text-indigo-600 hover:underline">Add one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($suppliers->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $suppliers->links() }}</div>
        @endif
    </div>
</div>