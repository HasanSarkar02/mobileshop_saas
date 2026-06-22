<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search suppliers…"
            class="input max-w-xs">
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
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <div class="font-medium text-gray-900">{{ $supplier->name }}</div>
                                @if ($supplier->address)
                                    <div class="text-xs text-gray-400 truncate max-w-[200px]">{{ $supplier->address }}
                                    </div>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="text-gray-900">{{ $supplier->phone ?? '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $supplier->email ?? '' }}</div>
                            </td>
                            <td class="table-td">
                                <span class="badge badge-gray">{{ $supplier->purchases_count }}</span>
                            </td>
                            <td class="table-td">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('suppliers.edit', $supplier) }}" wire:navigate
                                        class="text-xs text-indigo-600 hover:underline font-medium">Edit</a>
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
                            <td colspan="4" class="table-td text-center text-gray-400 py-12">
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
