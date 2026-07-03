<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Expense Analysis</h2>
        <x-document.export-bar title="" :printUrl="route('reports.expenses.print', [
            'period' => $period,
            'branch' => $branchId,
            'from' => $dateFrom,
            'to' => $dateTo,
        ])" />
    </div>

    <x-report-filter :period="$period" :dateFrom="$dateFrom" :dateTo="$dateTo" :branchId="$branchId" :branches="$branches" />

    @php $agg = $this->aggregate; @endphp

    {{-- Pending Banner --}}
    @if ($this->pendingCount > 0)
        <div class="card p-4 bg-amber-50 border-amber-300 flex items-center gap-4">
            <div class="text-amber-800 text-sm font-medium flex-1">
                ⏳ {{ $this->pendingCount }} expense(s) pending approval — not included in these totals.
            </div>
            <a href="{{ route('expenses.index') }}?status=pending" wire:navigate
                class="btn-sm bg-amber-600 text-white hover:bg-amber-700 rounded-lg px-3 py-1.5 text-xs font-medium">
                Review →
            </a>
        </div>
    @endif

    {{-- KPI --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-5 border-0 bg-red-50">
            <div class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Total Expenses</div>
            <div class="text-2xl font-bold text-red-800">৳{{ number_format($agg->total, 2) }}</div>
            <div class="text-xs text-red-400 mt-1">{{ number_format($agg->count) }} entries</div>
        </div>
        <div class="card p-5 border-0 bg-gray-50">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Daily Average</div>
            <div class="text-2xl font-bold text-gray-800">
                ৳{{ $this->trend->count() > 0 ? number_format($agg->total / $this->trend->count(), 0) : 0 }}
            </div>
            <div class="text-xs text-gray-400 mt-1">per day with activity</div>
        </div>
        <div class="card p-5 border-0 bg-indigo-50">
            <div class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-1">Categories</div>
            <div class="text-2xl font-bold text-indigo-800">{{ $this->byCategory->count() }}</div>
            <div class="text-xs text-indigo-400 mt-1">active in period</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card overflow-hidden">
        <nav class="flex border-b border-gray-200 overflow-x-auto">
            @foreach ([['key' => 'summary', 'label' => 'By Category'], ['key' => 'branch', 'label' => 'By Branch'], ['key' => 'trend', 'label' => 'Daily Trend'], ['key' => 'list', 'label' => 'Full List']] as $tab)
                <button wire:click="$set('activeView', '{{ $tab['key'] }}')"
                    class="px-5 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                        {{ $activeView === $tab['key'] ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>

        {{-- By Category --}}
        <div wire:show="activeView === 'summary'" class="p-5">
            @php $total = $this->byCategory->sum('total'); @endphp
            <div class="space-y-3">
                @forelse($this->byCategory as $cat)
                    @php $pct = $total > 0 ? round($cat->total / $total * 100, 1) : 0; @endphp
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between text-sm">
                            <div>
                                <span class="font-medium text-gray-900">{{ $cat->category }}</span>
                                @if ($cat->parent_category !== $cat->category)
                                    <span class="text-gray-400 text-xs ml-1">in {{ $cat->parent_category }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="text-xs text-gray-400">{{ $cat->count }} entries</span>
                                <span class="font-bold text-red-600">৳{{ number_format($cat->total, 0) }}</span>
                                <span class="text-xs text-gray-400 w-8 text-right">{{ $pct }}%</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-red-400 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-gray-400 py-8">No expenses in this period.</p>
                @endforelse
            </div>
        </div>

        {{-- By Branch --}}
        <div wire:show="activeView === 'branch'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Branch</th>
                        <th class="table-th text-right">Entries</th>
                        <th class="table-th text-right">Total</th>
                        <th class="table-th text-right">% Share</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $bTotal = $this->byBranch->sum('total'); @endphp
                    @forelse($this->byBranch as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-medium text-gray-900">{{ $row->name }}</td>
                            <td class="table-td text-right text-gray-500">{{ $row->count }}</td>
                            <td class="table-td text-right font-bold text-red-600">৳{{ number_format($row->total, 0) }}
                            </td>
                            <td class="table-td text-right text-gray-500">
                                {{ $bTotal > 0 ? round(($row->total / $bTotal) * 100, 1) : 0 }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="table-td text-center text-gray-400 py-8">No data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Daily Trend --}}
        <div wire:show="activeView === 'trend'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Date</th>
                        <th class="table-th text-right">Entries</th>
                        <th class="table-th text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->trend as $day)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-medium">
                                {{ \Carbon\Carbon::parse($day->expense_date)->format('d M Y') }}</td>
                            <td class="table-td text-right text-gray-500">{{ $day->count }}</td>
                            <td class="table-td text-right font-bold text-red-600">৳{{ number_format($day->total, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="table-td text-center text-gray-400 py-8">No data.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($this->trend->isNotEmpty())
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                        <tr>
                            <td class="table-td font-bold">Total</td>
                            <td class="table-td text-right font-bold">{{ $this->trend->sum('count') }}</td>
                            <td class="table-td text-right font-bold text-red-700">
                                ৳{{ number_format($this->trend->sum('total'), 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Full List --}}
        <div wire:show="activeView === 'list'" class="overflow-x-auto">
            @if ($expenses)
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Date</th>
                            <th class="table-th">Category</th>
                            <th class="table-th">Description</th>
                            <th class="table-th">Branch</th>
                            <th class="table-th">Paid Via</th>
                            <th class="table-th text-right">Amount</th>
                            <th class="table-th">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($expenses as $exp)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td text-gray-500 text-sm">
                                    {{ \Carbon\Carbon::parse($exp->expense_date)->format('d M Y') }}</td>
                                <td class="table-td">
                                    <div class="text-sm font-medium text-gray-900">{{ $exp->sub_category }}</div>
                                    @if ($exp->category_name !== $exp->sub_category)
                                        <div class="text-xs text-gray-400">{{ $exp->category_name }}</div>
                                    @endif
                                </td>
                                <td class="table-td text-gray-700 text-sm">{{ $exp->description }}</td>
                                <td class="table-td text-xs text-gray-500">{{ $exp->branch_name }}</td>
                                <td class="table-td text-xs text-gray-500">{{ $exp->payment_account_name }}</td>
                                <td class="table-td text-right font-bold text-red-600">
                                    ৳{{ number_format($exp->amount, 2) }}</td>
                                <td class="table-td text-xs text-gray-400">{{ $exp->created_by_name }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="table-td text-center text-gray-400 py-8">No expenses.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($expenses->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $expenses->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</div>
