<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Account Statement</h2>
        @if ($accountId && $this->statement)
            <x-document.export-bar title="{{ $this->statement->account->name }} — {{ $periodLabel }}"
                :printUrl="route('reports.account-statement.print', [
                    'account' => $accountId,
                    'period' => $period,
                    'from' => $dateFrom,
                    'to' => $dateTo,
                ])" />
        @endif
    </div>

    {{-- Filters --}}
    <div class="card p-4 space-y-3">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="label text-xs">Account *</label>
                <select wire:model.live="accountId" class="input text-sm">
                    <option value="0">Select account…</option>
                    @foreach ($this->paymentAccounts as $acc)
                        <option value="{{ $acc->id }}">
                            {{ $acc->name }} ({{ ucfirst($acc->provider ?? 'other') }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label text-xs">Period</label>
                <div class="flex flex-wrap gap-1">
                    @foreach (\App\Reporting\Enums\ReportPeriod::cases() as $p)
                        @if ($p !== \App\Reporting\Enums\ReportPeriod::Custom)
                            <button wire:click="$set('period', '{{ $p->value }}')"
                                class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors
                                    {{ $period === $p->value ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
                                {{ $p->label() }}
                            </button>
                        @endif
                    @endforeach
                    <button wire:click="$set('period', 'custom')"
                        class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors
                            {{ $period === 'custom' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">
                        Custom
                    </button>
                </div>
            </div>
            @if ($period === 'custom')
                <div class="flex items-end gap-2">
                    <div>
                        <label class="label text-xs">From</label>
                        <input wire:model.live="dateFrom" type="date" class="input text-sm w-36">
                    </div>
                    <span class="text-gray-400 text-sm pb-1">–</span>
                    <div>
                        <label class="label text-xs">To</label>
                        <input wire:model.live="dateTo" type="date" class="input text-sm w-36">
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if (!$accountId)
        <div class="card p-10 text-center text-gray-400">
            Select an account above to view its statement.
        </div>
    @elseif($stmt = $this->statement)
        {{-- Summary cards --}}
        <div class="grid grid-cols-3 gap-4">
            <div class="card p-4 border-0 bg-gray-50">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Opening Balance</div>
                <div class="text-xl font-bold {{ $stmt->opening_balance < 0 ? 'text-red-700' : 'text-gray-900' }}">
                    ৳{{ number_format($stmt->opening_balance, 2) }}
                </div>
            </div>
            <div class="card p-4 border-0 bg-green-50">
                <div class="text-xs font-semibold text-green-500 uppercase tracking-wider mb-1">Total Inflow</div>
                <div class="text-xl font-bold text-green-700">+৳{{ number_format($stmt->period_debits, 2) }}</div>
            </div>
            <div class="card p-4 border-0 bg-red-50">
                <div class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Total Outflow</div>
                <div class="text-xl font-bold text-red-700">-৳{{ number_format($stmt->period_credits, 2) }}</div>
            </div>
        </div>

        {{-- Transactions Table --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 bg-indigo-700 text-white flex items-center justify-between">
                <div>
                    <div class="font-bold">{{ $stmt->account->name }}</div>
                    <div class="text-indigo-200 text-sm">{{ $periodLabel }}</div>
                </div>
                <div class="text-right">
                    <div class="text-indigo-200 text-xs">Closing Balance</div>
                    <div class="font-bold text-lg {{ $stmt->closing_balance < 0 ? 'text-red-300' : 'text-white' }}">
                        ৳{{ number_format($stmt->closing_balance, 2) }}
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Date</th>
                            <th class="table-th">Entry No.</th>
                            <th class="table-th">Description</th>
                            <th class="table-th text-right">Inflow (Dr)</th>
                            <th class="table-th text-right">Outflow (Cr)</th>
                            <th class="table-th text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {{-- Opening balance row --}}
                        <tr class="bg-gray-50">
                            <td class="table-td text-xs text-gray-500">
                                {{ $stmt->date_range->from->subDay()->format('d M Y') }}
                            </td>
                            <td class="table-td"></td>
                            <td class="table-td font-semibold text-gray-700">Opening Balance</td>
                            <td class="table-td text-right"></td>
                            <td class="table-td text-right"></td>
                            <td class="table-td text-right font-bold text-indigo-700">
                                ৳{{ number_format($stmt->opening_balance, 2) }}
                            </td>
                        </tr>
                        @forelse($stmt->lines as $line)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td text-xs text-gray-500 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($line->entry_date)->format('d M Y') }}
                                </td>
                                <td class="table-td font-mono text-xs text-gray-400">
                                    {{ $line->entry_number }}
                                </td>
                                <td class="table-td">
                                    <div class="text-sm text-gray-900">
                                        {{ $line->line_description ?: $line->entry_description }}
                                    </div>
                                    @if ($line->line_description && $line->entry_description !== $line->line_description)
                                        <div class="text-xs text-gray-400">{{ $line->entry_description }}</div>
                                    @endif
                                    @if ($line->reference_type)
                                        <div class="text-xs text-indigo-400">
                                            {{ class_basename($line->reference_type) }} #{{ $line->reference_id }}
                                        </div>
                                    @endif
                                </td>
                                <td
                                    class="table-td text-right {{ $line->debit > 0 ? 'text-green-600 font-semibold' : 'text-gray-300' }}">
                                    {{ $line->debit > 0 ? '+৳' . number_format($line->debit, 2) : '—' }}
                                </td>
                                <td
                                    class="table-td text-right {{ $line->credit > 0 ? 'text-red-600 font-semibold' : 'text-gray-300' }}">
                                    {{ $line->credit > 0 ? '-৳' . number_format($line->credit, 2) : '—' }}
                                </td>
                                <td
                                    class="table-td text-right font-bold
                                    {{ $line->running_balance < 0 ? 'text-red-700' : 'text-gray-900' }}">
                                    ৳{{ number_format($line->running_balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="table-td text-center text-gray-400 py-8">
                                    No transactions in this period.
                                </td>
                            </tr>
                        @endforelse
                        {{-- Closing balance row --}}
                        @if ($stmt->lines->isNotEmpty())
                            <tr class="bg-indigo-50 border-t-2 border-indigo-200">
                                <td colspan="3" class="table-td font-bold text-indigo-900">Closing Balance</td>
                                <td class="table-td text-right font-bold text-green-700">
                                    +৳{{ number_format($stmt->period_debits, 2) }}
                                </td>
                                <td class="table-td text-right font-bold text-red-700">
                                    -৳{{ number_format($stmt->period_credits, 2) }}
                                </td>
                                <td class="table-td text-right font-bold text-indigo-700 text-base">
                                    ৳{{ number_format($stmt->closing_balance, 2) }}
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
