<div class="space-y-6">
    {{-- Stats Grid --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach ([['label' => 'Total Shops', 'value' => $stats['total'], 'color' => 'bg-indigo-50 text-indigo-700'], ['label' => 'Active', 'value' => $stats['active'], 'color' => 'bg-green-50 text-green-700'], ['label' => 'Trial', 'value' => $stats['trial'], 'color' => 'bg-blue-50 text-blue-700'], ['label' => 'Suspended', 'value' => $stats['suspended'], 'color' => 'bg-red-50 text-red-700'], ['label' => 'Expired', 'value' => $stats['expired'], 'color' => 'bg-gray-50 text-gray-700']] as $stat)
            <div class="card p-4 {{ $stat['color'] }} border-0">
                <div class="text-2xl font-bold">{{ $stat['value'] }}</div>
                <div class="text-xs font-medium mt-1 opacity-80">{{ $stat['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Recent Shops --}}
    <div class="card">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Recent Shops</h3>
            <a href="{{ route('admin.shops') }}" wire:navigate
                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Shop</th>
                        <th class="table-th">Owner</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($recentShops as $shop)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <a href="{{ route('admin.shops.show', $shop) }}" wire:navigate
                                    class="font-medium text-indigo-600 hover:text-indigo-800">{{ $shop->name }}</a>
                            </td>
                            <td class="table-td text-gray-500">{{ $shop->owner?->email ?? '—' }}</td>
                            <td class="table-td">
                                @include('partials.shop-status-badge', ['status' => $shop->status])
                            </td>
                            <td class="table-td text-gray-500">{{ $shop->created_at->format('d M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
