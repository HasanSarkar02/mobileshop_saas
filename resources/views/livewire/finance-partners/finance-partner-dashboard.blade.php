<div class="space-y-5">

    {{-- ── Partner Cards (Summary) ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($this->partners as $fp)
            @php $summary = $this->partnerSummaries[$fp->id] ?? null; @endphp
            <button wire:click="$set('selectedPartnerId', {{ $fp->id }})"
                class="card p-5 text-left transition-all hover:shadow-md border-2
                    {{ $selectedPartnerId === $fp->id ? 'border-indigo-500' : 'border-transparent hover:border-gray-200' }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-gray-900">{{ $fp->name }}</h3>
                        @if ($fp->processing_fee_percent > 0)
                            <p class="text-xs text-gray-400 mt-0.5">{{ $fp->processing_fee_percent }}% fee</p>
                        @endif
                    </div>
                    <span class="badge badge-green text-xs">Active</span>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <div class="text-xs text-gray-400">Pending (Due)</div>
                        <div class="font-bold text-red-600 text-lg">
                            ৳{{ number_format($summary['total_pending'] ?? 0, 0) }}
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $summary['pending_count'] ?? 0 }} receivable(s)
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">Total Settled</div>
                        <div class="font-bold text-green-600 text-lg">
                            ৳{{ number_format($summary['total_settled'] ?? 0, 0) }}
                        </div>
                    </div>
                </div>
                @if (($summary['pending_count'] ?? 0) > 0)
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <a href="{{ route('finance-partners.record-settlement', $fp) }}" wire:navigate onclick.stop
                            class="btn-primary btn-sm w-full text-center text-xs">
                            Record Settlement →
                        </a>
                    </div>
                @endif
            </button>
        @endforeach

        @if ($this->partners->isEmpty())
            <div class="col-span-3 card p-10 text-center text-gray-400">
                <p class="text-sm">No finance partners yet.</p>
                <a href="{{ route('settings') }}#finance_partners" wire:navigate
                    class="text-indigo-600 hover:underline text-sm mt-1 inline-block">
                    Add TopPay, PalmPay in Settings →
                </a>
            </div>
        @endif
    </div>

    {{-- ── Partner Detail ── --}}
    @if ($partner)
        <div class="card overflow-hidden">
            {{-- Receivables Tab Filter --}}
            <div wire:show="activeTab === 'receivables'"
                class="px-5 py-2 border-b border-gray-100 bg-gray-50 flex items-center gap-3">
                <span class="text-xs text-gray-500">Show:</span>
                <div class="flex gap-1">
                    @foreach (['pending' => 'Active Only', 'all' => 'All (incl. cancelled/settled)'] as $key => $label)
                        <button wire:click="$set('receivableFilter', '{{ $key }}')"
                            class="px-3 py-1 rounded-lg text-xs font-medium transition-colors
                                {{ $receivableFilter === $key ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-bold text-gray-900">{{ $partner->name }}</h3>
                <div class="flex gap-2">
                    <a href="{{ route('finance-partners.record-settlement', $partner) }}" wire:navigate
                        class="btn-primary btn-sm">+ Record Settlement</a>
                </div>
            </div>

            {{-- Tabs --}}
            <nav class="flex border-b border-gray-200">
                @foreach ([['key' => 'receivables', 'label' => 'Receivables (Pending)'], ['key' => 'settlements', 'label' => 'Settlement History']] as $tab)
                    <button wire:click="$set('activeTab', '{{ $tab['key'] }}')"
                        class="px-5 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                            {{ $activeTab === $tab['key']
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        {{ $tab['label'] }}
                    </button>
                @endforeach
            </nav>

            {{-- Receivables Tab --}}
            <div wire:show="activeTab === 'receivables'">
                @if ($receivables)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="table-th">Invoice</th>
                                    <th class="table-th">Customer</th>
                                    <th class="table-th">Sale Date</th>
                                    <th class="table-th text-right">Total</th>
                                    <th class="table-th text-right">Settled</th>
                                    <th class="table-th text-right">Pending</th>
                                    <th class="table-th">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($receivables as $rec)
                                    <tr class="hover:bg-gray-50">
                                        <td class="table-td font-mono font-semibold text-indigo-600 text-sm">
                                            {{ $rec->sale?->sale_number ?? '—' }}
                                        </td>
                                        <td class="table-td">
                                            <div class="font-medium text-sm">
                                                {{ $rec->sale?->customer?->name ?? 'Walk-in' }}
                                            </div>
                                            <div class="text-xs text-gray-400">
                                                {{ $rec->sale?->customer?->phone }}
                                            </div>
                                        </td>
                                        <td class="table-td text-gray-500 text-xs">
                                            {{ $rec->sale?->confirmed_at?->format('d M Y') ?? '—' }}
                                        </td>
                                        <td class="table-td text-right font-semibold">
                                            ৳{{ number_format($rec->total_amount, 2) }}
                                        </td>
                                        <td class="table-td text-right text-green-600 font-medium">
                                            ৳{{ number_format($rec->settled_amount, 2) }}
                                        </td>
                                        <td
                                            class="table-td text-right font-bold {{ $rec->pendingAmount() > 0 ? 'text-red-600' : 'text-green-600' }}">
                                            ৳{{ number_format($rec->pendingAmount(), 2) }}
                                        </td>
                                        <td class="table-td">
                                            <span class="badge {{ $rec->status->badgeClass() }}">
                                                {{ $rec->status->label() }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="table-td text-center text-gray-400 py-8">
                                            No pending receivables. 🎉 All settled!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($receivables->hasPages())
                        <div class="px-4 py-3 border-t border-gray-100">{{ $receivables->links() }}</div>
                    @endif
                @endif
            </div>

            {{-- Settlements Tab --}}
            <div wire:show="activeTab === 'settlements'">
                @if ($settlements)
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th class="table-th">Date</th>
                                    <th class="table-th">Reference</th>
                                    <th class="table-th text-right">Gross</th>
                                    <th class="table-th text-right">Fee</th>
                                    <th class="table-th text-right">Net Received</th>
                                    <th class="table-th text-right">Allocated</th>
                                    <th class="table-th">Account</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($settlements as $s)
                                    <tr class="hover:bg-gray-50">
                                        <td class="table-td text-gray-500 text-sm">
                                            {{ $s->settlement_date->format('d M Y') }}
                                        </td>
                                        <td class="table-td font-mono text-xs text-gray-700">
                                            {{ $s->reference_number ?? '—' }}
                                        </td>
                                        <td class="table-td text-right">৳{{ number_format($s->gross_amount, 2) }}</td>
                                        <td class="table-td text-right text-red-500">
                                            @if ($s->fee_deducted > 0)
                                                −৳{{ number_format($s->fee_deducted, 2) }}
                                            @else
                                                <span class="text-gray-300">—</span>
                                            @endif
                                        </td>
                                        <td class="table-td text-right font-bold text-green-700">
                                            ৳{{ number_format($s->net_amount, 2) }}
                                        </td>
                                        <td class="table-td text-right text-gray-600">
                                            ৳{{ number_format($s->allocated_amount, 2) }}
                                            <div class="text-xs text-gray-400">
                                                {{ $s->allocations->count() }} invoice(s)
                                            </div>
                                        </td>
                                        <td class="table-td text-gray-500 text-xs">
                                            {{ $s->paymentAccount?->name }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="table-td text-center text-gray-400 py-8">
                                            No settlements recorded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($settlements->hasPages())
                        <div class="px-4 py-3 border-t border-gray-100">{{ $settlements->links() }}</div>
                    @endif
                @endif
            </div>
        </div>
    @endif
</div>
