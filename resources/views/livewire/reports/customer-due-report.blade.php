<div class="space-y-5">

    <h2 class="text-xl font-bold text-gray-900">Receivables & Due Report</h2>

    {{-- Summary --}}
    @php $cs = $this->customerStats; @endphp
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 border-0 bg-amber-50">
            <div class="text-2xl font-bold text-amber-700">{{ number_format($cs->with_due) }}</div>
            <div class="text-xs font-medium text-amber-500 mt-0.5">Customers with Due</div>
        </div>
        <div class="card p-4 border-0 bg-red-50">
            <div class="text-2xl font-bold text-red-700">৳{{ number_format($cs->total_outstanding, 0) }}</div>
            <div class="text-xs font-medium text-red-500 mt-0.5">Total Customer Due</div>
        </div>
        <div class="card p-4 border-0 bg-indigo-50">
            <div class="text-2xl font-bold text-indigo-700">
                ৳{{ number_format($this->fpReceivables->sum('pending_amount'), 0) }}
            </div>
            <div class="text-xs font-medium text-indigo-500 mt-0.5">Finance Partner Receivable</div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="card overflow-hidden">
        <nav class="flex border-b border-gray-200">
            @foreach ([['key' => 'customers', 'label' => 'Customer Dues'], ['key' => 'finance_partners', 'label' => 'Finance Partner Receivables']] as $tab)
                <button wire:click="$set('activeView', '{{ $tab['key'] }}')"
                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors
                        {{ $activeView === $tab['key']
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>

        {{-- Customer Dues --}}
        <div wire:show="activeView === 'customers'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Customer</th>
                        <th class="table-th">Phone</th>
                        <th class="table-th">Type</th>
                        <th class="table-th text-right">Total Purchases</th>
                        <th class="table-th text-right">Credit Limit</th>
                        <th class="table-th text-right">Outstanding Due</th>
                        <th class="table-th">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->customerDues as $c)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <div class="font-semibold text-sm text-gray-900">{{ $c->name }}</div>
                                <div class="text-xs text-gray-400">Since
                                    {{ \Carbon\Carbon::parse($c->created_at)->format('M Y') }}</div>
                            </td>
                            <td class="table-td text-gray-600 text-sm">{{ $c->phone }}</td>
                            <td class="table-td">
                                <span
                                    class="badge badge-gray text-xs capitalize">{{ str_replace('_', ' ', $c->customer_type) }}</span>
                            </td>
                            <td class="table-td text-right text-gray-700">
                                ৳{{ number_format($c->total_purchase_amount, 0) }}
                            </td>
                            <td class="table-td text-right text-gray-500">
                                {{ $c->credit_limit > 0 ? '৳' . number_format($c->credit_limit, 0) : '∞ Unlimited' }}
                            </td>
                            <td class="table-td text-right">
                                <span class="font-bold text-red-600 text-base">
                                    ৳{{ number_format($c->current_balance, 2) }}
                                </span>
                            </td>
                            <td class="table-td">
                                <a href="{{ route('customers.show', $c->id) }}" wire:navigate
                                    class="text-xs text-indigo-600 hover:underline font-medium">
                                    Collect →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-td text-center text-gray-400 py-10">
                                🎉 No outstanding customer dues!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($this->customerDues->isNotEmpty())
                    <tfoot class="bg-red-50 border-t-2 border-red-200">
                        <tr>
                            <td colspan="5" class="table-td font-bold text-red-900">Total Outstanding</td>
                            <td class="table-td text-right font-bold text-red-700 text-lg">
                                ৳{{ number_format($this->customerDues->sum('current_balance'), 2) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Finance Partner Receivables --}}
        <div wire:show="activeView === 'finance_partners'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Partner</th>
                        <th class="table-th">Invoice</th>
                        <th class="table-th">Customer</th>
                        <th class="table-th">Sale Date</th>
                        <th class="table-th text-right">Total</th>
                        <th class="table-th text-right">Settled</th>
                        <th class="table-th text-right">Pending</th>
                        <th class="table-th text-center">Days Old</th>
                        <th class="table-th">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->fpReceivables as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-semibold text-sm text-gray-900">{{ $row->partner_name }}</td>
                            <td class="table-td font-mono text-indigo-600 text-sm">{{ $row->sale_number }}</td>
                            <td class="table-td text-gray-600 text-sm">{{ $row->customer_name }}</td>
                            <td class="table-td text-gray-500 text-xs">
                                {{ \Carbon\Carbon::parse($row->sale_date)->format('d M Y') }}
                            </td>
                            <td class="table-td text-right">৳{{ number_format($row->total_amount, 0) }}</td>
                            <td class="table-td text-right text-green-600">
                                ৳{{ number_format($row->settled_amount, 0) }}</td>
                            <td class="table-td text-right font-bold text-red-600">
                                ৳{{ number_format($row->pending_amount, 0) }}</td>
                            <td class="table-td text-center">
                                <span
                                    class="badge {{ $row->days_outstanding > 30 ? 'badge-red' : ($row->days_outstanding > 14 ? 'badge-yellow' : 'badge-green') }}">
                                    {{ $row->days_outstanding }}d
                                </span>
                            </td>
                            <td class="table-td">
                                <span class="badge badge-yellow text-xs capitalize">{{ $row->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-td text-center text-gray-400 py-10">
                                🎉 No pending finance partner receivables!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($this->fpReceivables->isNotEmpty())
                    <tfoot class="bg-red-50 border-t-2 border-red-200">
                        <tr>
                            <td colspan="6" class="table-td font-bold text-red-900">Total Pending</td>
                            <td class="table-td text-right font-bold text-red-700 text-base">
                                ৳{{ number_format($this->fpReceivables->sum('pending_amount'), 0) }}
                            </td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
