<div class="space-y-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <div class="flex-1">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search by name or email…"
                class="input max-w-xs">
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="status" class="input w-auto">
                <option value="">All statuses</option>
                @foreach (App\Enums\ShopStatus::cases() as $s)
                    <option value="{{ $s->value }}">{{ ucfirst($s->value) }}</option>
                @endforeach
            </select>
            <a href="{{ route('admin.shops.create') }}" wire:navigate class="btn-primary whitespace-nowrap">
                + New Shop
            </a>
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Shop</th>
                        <th class="table-th">Owner</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Trial Ends</th>
                        <th class="table-th">Created</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($shops as $shop)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <a href="{{ route('admin.shops.show', $shop) }}" wire:navigate
                                    class="font-semibold text-indigo-600 hover:text-indigo-800">
                                    {{ $shop->name }}
                                </a>
                                <div class="text-xs text-gray-400">{{ $shop->slug }}</div>
                            </td>
                            <td class="table-td">
                                <div class="font-medium text-gray-900">{{ $shop->owner?->name ?? '—' }}</div>
                                <div class="text-xs text-gray-400">{{ $shop->owner?->email }}</div>
                            </td>
                            <td class="table-td">
                                @include('partials.shop-status-badge', ['status' => $shop->status])
                            </td>
                            <td class="table-td text-gray-500 text-xs">
                                {{ $shop->trial_ends_at?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="table-td text-gray-500 text-xs">
                                {{ $shop->created_at->format('d M Y') }}
                            </td>
                            <td class="table-td">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.shops.show', $shop) }}" wire:navigate
                                        class="text-xs text-indigo-600 hover:underline font-medium">View</a>
                                    @if ($shop->is_active)
                                        <button wire:click="suspend({{ $shop->id }})"
                                            wire:confirm="Suspend {{ $shop->name }}?"
                                            class="text-xs text-red-600 hover:underline font-medium">Suspend</button>
                                    @else
                                        <button wire:click="activate({{ $shop->id }})"
                                            class="text-xs text-green-600 hover:underline font-medium">Activate</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-td text-center text-gray-400 py-12">No shops found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($shops->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $shops->links() }}
            </div>
        @endif
    </div>
</div>
