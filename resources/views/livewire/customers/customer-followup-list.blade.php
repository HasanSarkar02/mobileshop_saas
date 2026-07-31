<div>
    <h1 class="text-xl font-semibold mb-4">Due Follow-ups</h1>

    <div class="flex flex-wrap gap-2 mb-4">
        @foreach ([['key' => 'today', 'label' => 'Today'], ['key' => 'overdue', 'label' => 'Overdue'], ['key' => 'promised', 'label' => 'Broken Promises'], ['key' => 'all', 'label' => 'All Open']] as $tab)
            <button wire:click="$set('view', '{{ $tab['key'] }}')"
                class="px-3 py-1.5 rounded-lg text-sm font-medium border
                    {{ $view === $tab['key'] ? 'bg-indigo-50 border-indigo-300 text-indigo-700' : 'border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ $tab['label'] }}
            </button>
        @endforeach
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Customer</th>
                        <th class="table-th">Outstanding Due</th>
                        <th class="table-th">Next Follow-up</th>
                        <th class="table-th">Promise Date</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($followUps as $f)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <div class="font-semibold text-sm text-gray-900">{{ $f->customer->name }}</div>
                                <div class="text-xs text-gray-400">{{ $f->customer->phone }}</div>
                            </td>
                            <td class="table-td font-semibold text-red-600">
                                ৳{{ number_format($f->customer->current_balance, 2) }}</td>
                            <td class="table-td text-sm">{{ $f->next_followup_date?->format('d M Y, h:i A') ?? '—' }}
                            </td>
                            <td class="table-td text-sm">{{ $f->promised_payment_date?->format('d M Y, h:i A') ?? '—' }}
                            </td>
                            <td class="table-td"><span
                                    class="badge {{ $f->status->badgeClass() }}">{{ $f->status->label() }}</span></td>
                            <td class="table-td">
                                <a href="{{ route('customers.show', $f->customer) }}?tab=followup" wire:navigate
                                    class="text-xs text-indigo-600 hover:underline font-medium">View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-td text-center text-gray-400 py-10">Nothing here.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">{{ $followUps->links() }}</div>
    </div>
</div>
