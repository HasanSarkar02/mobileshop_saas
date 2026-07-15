<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Supplier Ledger</h2>
        @if ($supplierId && $this->ledger)
            <x-document.export-bar title="{{ $this->ledger->supplier->name }}" :printUrl="route('documents.supplier-statement', [
                'supplier' => $supplierId,
                'from' => $dateFrom,
                'to' => $dateTo,
                'period' => $period,
            ])" />
        @endif
    </div>

    <div class="card p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="label text-xs">Supplier *</label>
            <select wire:model.live="supplierId" class="input text-sm w-56">
                <option value="0">Select supplier…</option>
                @foreach ($this->suppliers as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <x-report-filter :period="$period" :dateFrom="$dateFrom" :dateTo="$dateTo" :branchId="$branchId" :branches="$this->getBranchesProperty()"
            :showBranch="false" />
    </div>

    @if (!$supplierId)
        <div class="card p-10 text-center text-gray-400">Select a supplier to view their ledger.</div>
    @elseif($lg = $this->ledger)
        <div class="grid grid-cols-3 gap-4">
            <div class="card p-4 border-0 bg-gray-50">
                <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Opening Balance</div>
                <div class="text-xl font-bold text-gray-900">৳{{ number_format($lg->opening, 2) }}</div>
            </div>
            <div class="card p-4 border-0 bg-red-50">
                <div class="text-xs font-semibold text-red-500 uppercase mb-1">Purchased</div>
                <div class="text-xl font-bold text-red-700">+৳{{ number_format($lg->total_dr, 2) }}</div>
            </div>
            <div class="card p-4 border-0 {{ $lg->closing > 0 ? 'bg-amber-50' : 'bg-green-50' }}">
                <div
                    class="text-xs font-semibold {{ $lg->closing > 0 ? 'text-amber-500' : 'text-green-500' }} uppercase mb-1">
                    Closing Balance</div>
                <div class="text-xl font-bold {{ $lg->closing > 0 ? 'text-amber-700' : 'text-green-700' }}">
                    ৳{{ number_format($lg->closing, 2) }}</div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="px-5 py-3 bg-gray-800 text-white flex justify-between">
                <div>
                    <div class="font-bold">{{ $lg->supplier->name }}</div>
                    <div class="text-gray-300 text-sm">{{ $periodLabel }}</div>
                </div>
                <a href="{{ route('suppliers.show', $lg->supplier) }}" wire:navigate
                    class="text-xs text-gray-300 hover:text-white self-end">View Profile →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Date</th>
                            <th class="table-th">Type</th>
                            <th class="table-th">Reference</th>
                            <th class="table-th text-right">Purchased (৳)</th>
                            <th class="table-th text-right">Paid/Returned (৳)</th>
                            <th class="table-th text-right">Balance (৳)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="bg-gray-50">
                            <td colspan="5" class="table-td font-semibold text-gray-600">Opening Balance</td>
                            <td class="table-td text-right font-bold text-gray-900">
                                ৳{{ number_format($lg->opening, 2) }}</td>
                        </tr>
                        @forelse($lg->lines as $line)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td text-xs text-gray-500 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($line->txn_date)->format('d M Y') }}
                                </td>
                                <td class="table-td">
                                    <span
                                        class="badge {{ $line->txn_type === 'Purchase' ? 'badge-red' : ($line->txn_type === 'Payment' ? 'badge-green' : 'badge-yellow') }} text-xs">
                                        {{ $line->txn_type }}
                                    </span>
                                </td>
                                <td class="table-td font-mono text-xs text-gray-500">{{ $line->ref ?? '—' }}</td>
                                <td
                                    class="table-td text-right {{ $line->debit > 0 ? 'text-red-600 font-semibold' : 'text-gray-300' }}">
                                    {{ $line->debit > 0 ? '৳' . number_format($line->debit, 2) : '—' }}
                                </td>
                                <td
                                    class="table-td text-right {{ $line->credit > 0 ? 'text-green-600 font-semibold' : 'text-gray-300' }}">
                                    {{ $line->credit > 0 ? '৳' . number_format($line->credit, 2) : '—' }}
                                </td>
                                <td
                                    class="table-td text-right font-bold {{ $line->balance > 0 ? 'text-red-700' : 'text-green-600' }}">
                                    ৳{{ number_format($line->balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-td text-center text-gray-400 py-8">No transactions.</td>
                            </tr>
                        @endforelse
                        <tr class="bg-gray-50 border-t-2 border-gray-300 font-bold">
                            <td colspan="3" class="table-td text-gray-800">Closing Balance</td>
                            <td class="table-td text-right text-red-700">+৳{{ number_format($lg->total_dr, 2) }}</td>
                            <td class="table-td text-right text-green-700">-৳{{ number_format($lg->total_cr, 2) }}</td>
                            <td
                                class="table-td text-right text-base {{ $lg->closing > 0 ? 'text-red-800' : 'text-green-700' }}">
                                ৳{{ number_format($lg->closing, 2) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
