<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Used Phone Business Report</h2>
    </div>

    <x-report-filter :period="$period" :dateFrom="$dateFrom" :dateTo="$dateTo" :branchId="$branchId" :branches="$branches" />

    @php $s = $this->summary; @endphp

    {{-- P&L Summary --}}
    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ([['label' => 'Total Bought', 'value' => $s->total_count . ' phones', 'color' => 'gray'], ['label' => 'Total Spent', 'value' => '৳' . number_format($s->total_spent, 0), 'color' => 'red'], ['label' => 'Total Sold', 'value' => $s->sold_count . ' phones', 'color' => 'indigo'], ['label' => 'Revenue', 'value' => '৳' . number_format($s->total_revenue, 0), 'color' => 'green'], ['label' => 'Net Profit', 'value' => ($s->net_profit >= 0 ? '+' : '') . '৳' . number_format($s->net_profit, 0), 'color' => $s->net_profit >= 0 ? 'emerald' : 'red'], ['label' => 'Inventory Value', 'value' => '৳' . number_format($s->inventory_value, 0), 'color' => 'blue']] as $kpi)
            <div class="card p-4 border-0 bg-{{ $kpi['color'] }}-50">
                <div class="text-xs font-semibold text-{{ $kpi['color'] }}-500 uppercase tracking-wider mb-1">
                    {{ $kpi['label'] }}</div>
                <div class="text-xl font-bold text-{{ $kpi['color'] }}-800">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Tabs --}}
    <div class="card overflow-hidden">
        <nav class="flex border-b border-gray-200">
            @foreach ([['key' => 'summary', 'label' => 'Condition Breakdown'], ['key' => 'list', 'label' => 'Acquisition List']] as $tab)
                <button wire:click="$set('activeView', '{{ $tab['key'] }}')"
                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors
                        {{ $activeView === $tab['key'] ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>

        {{-- Condition Breakdown --}}
        <div wire:show="activeView === 'summary'" class="p-5">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Condition</th>
                        <th class="table-th text-right">Count</th>
                        <th class="table-th text-right">Total Spent</th>
                        <th class="table-th text-right">Expected Revenue</th>
                        <th class="table-th text-right">Expected Profit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->conditionBreakdown as $row)
                        @php $profit = $row->expected_revenue - $row->total_spent; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-medium capitalize">
                                {{ str_replace('_', ' ', $row->condition) }}
                            </td>
                            <td class="table-td text-right text-gray-600">{{ $row->count }}</td>
                            <td class="table-td text-right text-red-600 font-semibold">
                                ৳{{ number_format($row->total_spent, 0) }}</td>
                            <td class="table-td text-right text-blue-600 font-semibold">
                                ৳{{ number_format($row->expected_revenue, 0) }}</td>
                            <td
                                class="table-td text-right {{ $profit >= 0 ? 'text-green-600' : 'text-red-500' }} font-bold">
                                {{ $profit >= 0 ? '+' : '' }}৳{{ number_format($profit, 0) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-td text-center text-gray-400 py-8">No data.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Acquisition List --}}
        <div wire:show="activeView === 'list'" class="overflow-x-auto">
            @if ($acquisitions)
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="table-th">Ref</th>
                            <th class="table-th">Phone</th>
                            <th class="table-th">IMEI</th>
                            <th class="table-th">Condition</th>
                            <th class="table-th text-right">Paid</th>
                            <th class="table-th text-right">Expected Sell</th>
                            <th class="table-th">Status</th>
                            <th class="table-th">Branch</th>
                            <th class="table-th">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($acquisitions as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="table-td font-mono text-xs text-indigo-600">
                                    <a href="{{ route('used-phones.show', $row->id) }}" wire:navigate
                                        class="hover:underline">
                                        {{ $row->acquisition_number }}
                                    </a>
                                </td>
                                <td class="table-td">
                                    <div class="font-medium text-sm text-gray-900">{{ $row->model_description }}</div>
                                    @if ($row->catalog_name !== $row->model_description)
                                        <div class="text-xs text-indigo-400">{{ $row->catalog_name }}</div>
                                    @endif
                                </td>
                                <td class="table-td font-mono text-xs">{{ $row->imei_1 }}</td>
                                <td class="table-td text-xs capitalize">{{ str_replace('_', ' ', $row->condition) }}
                                </td>
                                <td class="table-td text-right font-semibold text-red-600">
                                    ৳{{ number_format($row->purchase_price, 0) }}</td>
                                <td class="table-td text-right text-blue-600">
                                    {{ $row->expected_sell_price > 0 ? '৳' . number_format($row->expected_sell_price, 0) : '—' }}
                                </td>
                                <td class="table-td">
                                    @php $uStatus = $row->unit_status ?? 'unknown'; @endphp
                                    <span
                                        class="badge {{ $uStatus === 'sold' ? 'badge-green' : ($uStatus === 'in_stock' ? 'badge-blue' : 'badge-gray') }} text-xs">
                                        {{ ucfirst(str_replace('_', ' ', $uStatus)) }}
                                    </span>
                                </td>
                                <td class="table-td text-xs text-gray-500">{{ $row->branch_name }}</td>
                                <td class="table-td text-xs text-gray-400">
                                    {{ \Carbon\Carbon::parse($row->created_at)->format('d M Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="table-td text-center text-gray-400 py-8">No acquisitions in
                                    this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if ($acquisitions->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">{{ $acquisitions->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</div>
