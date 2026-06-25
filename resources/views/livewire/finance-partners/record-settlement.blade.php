<div class="max-w-4xl mx-auto space-y-5">

    {{-- ── Header ── --}}
    <div class="card p-5 flex items-center gap-4">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-900">Record Settlement</h2>
            <p class="text-gray-500 text-sm mt-0.5">From: <strong>{{ $partner->name }}</strong></p>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-400">Total Pending</div>
            <div class="text-2xl font-bold text-red-600">৳{{ number_format($this->totalPendingAmount, 2) }}</div>
        </div>
    </div>

    {{-- ── Settlement Details ── --}}
    <div class="card p-6 space-y-4">
        <h3 class="font-semibold text-gray-900 border-b border-gray-100 pb-2">Settlement Details</h3>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="label">Settlement Date *</label>
                <input wire:model="settlementDate" type="date"
                    class="input @error('settlementDate') input-error @enderror">
                @error('settlementDate')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label">Received Into *</label>
                <select wire:model="paymentAccountId" class="input @error('paymentAccountId') input-error @enderror">
                    <option value="0">Select account…</option>
                    @foreach ($this->paymentAccounts as $pa)
                        <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                    @endforeach
                </select>
                @error('paymentAccountId')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label">Reference Number</label>
                <input wire:model="referenceNumber" type="text" class="input" placeholder="Bank ref / transfer ID">
            </div>
            <div>
                <label class="label">Gross Amount (৳) *</label>
                <input wire:model.live="grossAmount" type="number" step="0.01" min="0"
                    class="input @error('grossAmount') input-error @enderror"
                    placeholder="Amount as per bank statement">
                @error('grossAmount')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label">Fee Deducted by Partner (৳)</label>
                <input wire:model.live="feeDeducted" type="number" step="0.01" min="0" class="input"
                    placeholder="0">
                <p class="text-xs text-gray-400 mt-0.5">
                    @if ($partner->processing_fee_percent > 0)
                        Configured fee: {{ $partner->processing_fee_percent }}%
                    @else
                        No fee configured
                    @endif
                </p>
            </div>
            <div class="flex flex-col justify-end">
                <div class="bg-green-50 border border-green-200 rounded-xl p-3 text-center">
                    <div class="text-xs text-green-600 font-medium">Net Amount to Receive</div>
                    <div class="text-2xl font-bold text-green-700">
                        ৳{{ number_format($this->netAmount, 2) }}
                    </div>
                </div>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <label class="label">Notes</label>
                <input wire:model="notes" type="text" class="input" placeholder="Optional notes…">
            </div>
        </div>
    </div>

    {{-- ── Receivables Allocation ── --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-3">
            <div class="flex-1">
                <h3 class="font-semibold text-gray-900">Allocate Against Pending Receivables</h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    Select which sales this settlement covers. FIFO recommended (oldest first).
                </p>
            </div>
            <div class="flex gap-2 shrink-0">
                <button wire:click="selectAll" class="btn-secondary btn-sm">Select All</button>
                <button wire:click="deselectAll" class="btn-secondary btn-sm">Clear</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th w-10">
                            <input type="checkbox" x-data
                                @change="$wire.allocations.forEach((_, i) => { $wire.set('allocations.'+i+'.selected', $event.target.checked) })"
                                class="rounded border-gray-300 text-indigo-600">
                        </th>
                        <th class="table-th">Invoice</th>
                        <th class="table-th">Customer</th>
                        <th class="table-th">Sale Date</th>
                        <th class="table-th text-right">Total</th>
                        <th class="table-th text-right">Pending</th>
                        <th class="table-th text-right w-36">Allocate (৳)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($allocations as $idx => $alloc)
                        <tr wire:key="alloc-{{ $idx }}"
                            class="hover:bg-gray-50 {{ $alloc['selected'] ? 'bg-indigo-50' : '' }}">
                            <td class="table-td text-center">
                                <input wire:model.live="allocations.{{ $idx }}.selected" type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600">
                            </td>
                            <td class="table-td font-mono font-semibold text-indigo-600 text-sm">
                                {{ $alloc['sale_number'] ?? '—' }}
                            </td>
                            <td class="table-td">
                                <div class="font-medium text-sm text-gray-900">{{ $alloc['customer_name'] }}</div>
                                <div class="text-xs text-gray-400">{{ $alloc['customer_phone'] }}</div>
                            </td>
                            <td class="table-td text-gray-500 text-xs">{{ $alloc['sale_date'] }}</td>
                            <td class="table-td text-right text-gray-600">
                                ৳{{ number_format($alloc['total_amount'], 2) }}
                            </td>
                            <td class="table-td text-right font-bold text-red-600">
                                ৳{{ number_format($alloc['pending_amount'], 2) }}
                            </td>
                            <td class="table-td text-right">
                                @if ($alloc['selected'])
                                    <input wire:model.live="allocations.{{ $idx }}.alloc_amount"
                                        type="number" step="0.01" min="0"
                                        max="{{ $alloc['pending_amount'] }}"
                                        class="w-full text-right text-sm border border-indigo-300 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-indigo-400 focus:outline-none bg-white">
                                @else
                                    <span class="text-gray-300 text-sm">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-td text-center text-gray-400 py-10">
                                No pending receivables for {{ $partner->name }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Allocation Summary Footer --}}
        <div class="px-5 py-4 bg-gray-50 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="flex gap-6 text-sm">
                    <div>
                        <span class="text-gray-500">Net Received:</span>
                        <span class="font-bold text-green-700 ml-1">৳{{ number_format($this->netAmount, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Allocated:</span>
                        <span
                            class="font-bold text-indigo-700 ml-1">৳{{ number_format($this->totalAllocated, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Unallocated:</span>
                        <span
                            class="font-bold {{ $this->unallocatedBalance > 0.01 ? 'text-amber-600' : 'text-green-600' }} ml-1">
                            ৳{{ number_format($this->unallocatedBalance, 2) }}
                        </span>
                    </div>
                </div>
                @if ($this->unallocatedBalance > 0.01)
                    <p
                        class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5 sm:ml-auto">
                        ⚠ ৳{{ number_format($this->unallocatedBalance, 2) }} will not be allocated to any receivable.
                        You can still record the settlement and allocate the remainder later.
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex gap-3">
        <button wire:click="save" wire:loading.attr="disabled" wire:target="save" class="btn-primary">
            <span wire:loading.remove wire:target="save">Record Settlement</span>
            <span wire:loading wire:target="save" class="flex items-center gap-2">
                <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                        class="opacity-25" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                </svg>
                Processing…
            </span>
        </button>
        <a href="{{ route('finance-partners.index') }}" wire:navigate class="btn-secondary">Cancel</a>
    </div>
</div>
