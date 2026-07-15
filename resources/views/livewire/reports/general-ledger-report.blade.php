<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">General Ledger</h2>
        @if ($accountId && $this->ledger)
            <x-document.export-bar title="Ledger — {{ $this->ledger->account->name }}" :printUrl="route('reports.general-ledger.print', [
                'account' => $accountId,
                'period' => $period,
                'from' => $dateFrom,
                'to' => $dateTo,
            ])" />
        @endif
    </div>

    {{-- Filters --}}
    <div class="card p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="label text-xs">Account Type</label>
            <select wire:model.live="typeFilter" class="input text-sm">
                <option value="">All Types</option>
                @foreach (['asset', 'liability', 'equity', 'revenue', 'expense'] as $t)
                    <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label text-xs">Account *</label>
            <select wire:model.live="accountId" class="input text-sm w-64">
                <option value="0">Select account…</option>
                @foreach ($this->accounts as $acc)
                    <option value="{{ $acc->id }}">[{{ $acc->code }}] {{ $acc->name }}</option>
                @endforeach
            </select>
        </div>
        <x-report-filter :period="$period" :dateFrom="$dateFrom" :dateTo="$dateTo" :branchId="$branchId" :branches="$this->getBranchesProperty()"
            :showBranch="false" />
    </div>

    @if (!$accountId)
        <div class="card p-10 text-center text-gray-400">Select an account to view its ledger.</div>
    @elseif($lg = $this->ledger)
        {{-- Summary --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="card p-4 border-0 bg-gray-50">
                <div class="text-xs font-semibold text-gray-500 uppercase mb-1">Opening Balance</div>
                <div class="text-xl font-bold {{ $lg->opening < 0 ? 'text-red-700' : 'text-gray-900' }}">
                    ৳{{ number_format($lg->opening, 2) }}
                </div>
            </div>
            <div class="card p-4 border-0 bg-green-50">
                <div class="text-xs font-semibold text-green-500 uppercase mb-1">Period Debits</div>
                <div class="text-xl font-bold text-green-700">+৳{{ number_format($lg->total_dr, 2) }}</div>
            </div>
            <div class="card p-4 border-0 bg-indigo-50">
                <div class="text-xs font-semibold text-indigo-500 uppercase mb-1">Closing Balance</div>
                <div class="text-xl font-bold text-indigo-700">৳{{ number_format($lg->closing, 2) }}</div>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="px-5 py-3 bg-indigo-700 text-white flex justify-between">
                <div>
                    <div class="font-bold">[{{ $lg->account->code }}] {{ $lg->account->name }}</div>
                    <div class="text-indigo-200 text-sm">{{ $periodLabel }}</div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Date</th>
                            <th class="table-th">Entry No.</th>
                            <th class="table-th">Description</th>
                            <th class="table-th">Ref</th>
                            <th class="table-th text-right">Debit (৳)</th>
                            <th class="table-th text-right">Credit (৳)</th>
                            <th class="table-th text-right">Balance (৳)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr class="bg-gray-50">
                            <td colspan="6" class="table-td font-semibold text-gray-600">Opening Balance b/f</td>
                            <td class="table-td text-right font-bold text-indigo-700">
                                ৳{{ number_format($lg->opening, 2) }}
                            </td>
                        </tr>
                        @forelse($lg->lines as $line)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td text-xs text-gray-500 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($line->entry_date)->format('d M Y') }}
                                </td>
                                <td class="table-td font-mono text-xs text-gray-400">{{ $line->entry_number }}</td>
                                <td class="table-td text-sm text-gray-800">
                                    {{ $line->line_desc ?: $line->description }}
                                </td>
                                <td class="table-td text-xs text-gray-400">
                                    {{ $line->reference_type ? class_basename($line->reference_type) . '#' . $line->reference_id : '—' }}
                                </td>
                                <td
                                    class="table-td text-right {{ $line->debit > 0 ? 'text-green-700 font-semibold' : 'text-gray-300' }}">
                                    {{ $line->debit > 0 ? '+৳' . number_format($line->debit, 2) : '—' }}
                                </td>
                                <td
                                    class="table-td text-right {{ $line->credit > 0 ? 'text-red-600 font-semibold' : 'text-gray-300' }}">
                                    {{ $line->credit > 0 ? '-৳' . number_format($line->credit, 2) : '—' }}
                                </td>
                                <td
                                    class="table-td text-right font-bold {{ $line->balance < 0 ? 'text-red-700' : 'text-gray-900' }}">
                                    ৳{{ number_format($line->balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-td text-center text-gray-400 py-8">
                                    No transactions in this period.
                                </td>
                            </tr>
                        @endforelse
                        <tr class="bg-indigo-50 border-t-2 border-indigo-200 font-bold">
                            <td colspan="4" class="table-td text-indigo-900">Closing Balance c/f</td>
                            <td class="table-td text-right text-green-700">+৳{{ number_format($lg->total_dr, 2) }}</td>
                            <td class="table-td text-right text-red-700">-৳{{ number_format($lg->total_cr, 2) }}</td>
                            <td class="table-td text-right text-indigo-800 text-base">
                                ৳{{ number_format($lg->closing, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
