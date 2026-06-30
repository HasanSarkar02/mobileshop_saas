<div class="space-y-4">
    {{-- Today's Summary --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach ([['label' => "Today's Sales", 'value' => $this->todaySummary['count'] . ' orders', 'color' => 'bg-indigo-50 text-indigo-700'], ['label' => "Today's Net Revenue", 'value' => '৳' . number_format($this->todaySummary['revenue'], 2), 'color' => 'bg-green-50 text-green-700'], ['label' => "Today's Net Profit", 'value' => '৳' . number_format($this->todaySummary['profit'], 2), 'color' => 'bg-amber-50 text-amber-700']] as $card)
            <div class="card p-4 border-0 {{ $card['color'] }}">
                <div class="text-xl font-bold">{{ $card['value'] }}</div>
                <div class="text-xs font-medium opacity-75 mt-0.5">{{ $card['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Invoice no. or customer…"
            class="input max-w-xs">
        <select wire:model.live="status" class="input w-auto">
            <option value="">All statuses</option>
            @foreach (\App\Enums\SaleStatus::cases() as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
        <input wire:model.live="dateFrom" type="date" class="input w-auto">
        <input wire:model.live="dateTo" type="date" class="input w-auto">
        <a href="{{ route('pos') }}" wire:navigate class="btn-primary sm:ml-auto whitespace-nowrap">
            + New Sale (POS)
        </a>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Invoice</th>
                        <th class="table-th">Customer</th>
                        <th class="table-th">Branch</th>
                        <th class="table-th">Date</th>
                        <th class="table-th text-right">Total</th>
                        <th class="table-th text-right">Profit</th>
                        <th class="table-th">Payment</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sales as $sale)
                        <tr class="hover:bg-gray-50 {{ $sale->status->value === 'voided' ? 'opacity-60' : '' }}">
                            <td class="table-td font-mono font-semibold text-indigo-700 text-sm">
                                <a href="{{ route('sales.show', $sale) }}" wire:navigate class="hover:underline">
                                    {{ $sale->sale_number }}
                                </a>
                            </td>
                            <td class="table-td">
                                <div class="font-medium text-sm text-gray-900">
                                    {{ $sale->customer?->customer_type?->value === 'walk_in' ? 'Walk-in' : $sale->customer?->name }}
                                </div>
                                <div class="text-xs text-gray-400">{{ $sale->cashier?->name }}</div>
                            </td>
                            <td class="table-td text-gray-500 text-xs">{{ $sale->branch?->name }}</td>
                            <td class="table-td text-gray-500 text-xs">
                                {{ $sale->confirmed_at?->format('d M Y H:i') }}
                            </td>
                            <td class="table-td text-right font-bold text-gray-900">
                                ৳{{ number_format($sale->grand_total, 2) }}
                            </td>
                            <td
                                class="table-td text-right text-sm {{ $sale->gross_profit >= 0 ? 'text-green-600' : 'text-red-600' }} font-medium">
                                {{ $sale->gross_profit >= 0 ? '+' : '' }}৳{{ number_format($sale->gross_profit, 2) }}
                            </td>
                            <td class="table-td">
                                <div class="text-xs text-gray-500 max-w-[140px] truncate">
                                    {{ $sale->paymentSummary() }}
                                </div>
                            </td>
                            <td class="table-td">
                                @php $badge = $sale->displayStatusBadge(); @endphp
                                <span class="badge {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                            </td>
                            <td class="table-td">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('sales.show', $sale) }}" wire:navigate
                                        class="text-xs text-indigo-600 hover:underline font-medium">Detail</a>
                                    <a href="{{ route('sales.receipt', $sale) }}" target="_blank"
                                        class="text-xs text-indigo-600 hover:underline font-medium">Receipt</a>
                                    {{-- Only show Void if not returned and voidable --}}
                                    @if ($sale->isVoidable() && !$sale->return_processed)
                                        <button wire:click="openVoidModal({{ $sale->id }})"
                                            class="text-xs text-red-500 hover:underline font-medium">Void</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-td text-center text-gray-400 py-12">
                                No sales found.
                                <a href="{{ route('pos') }}" wire:navigate
                                    class="text-indigo-600 hover:underline ml-1">Start a sale</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sales->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $sales->links() }}</div>
        @endif
    </div>

    {{-- Void Modal --}}
    @if ($showVoidModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900 text-lg">Void Sale</h3>
                <p class="text-sm text-gray-500">
                    This will reverse all accounting entries and restore inventory.
                    This action cannot be undone.
                </p>
                <div>
                    <label class="label">Reason for void *</label>
                    <textarea wire:model="voidReason" rows="3" class="input"
                        placeholder="e.g. Customer changed mind, wrong item entered…"></textarea>
                    @error('voidReason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button wire:click="voidSale" wire:loading.attr="disabled" class="btn-danger flex-1">
                        <span wire:loading.remove>Void Sale</span>
                        <span wire:loading>Processing…</span>
                    </button>
                    <button wire:click="$set('showVoidModal', false)" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
