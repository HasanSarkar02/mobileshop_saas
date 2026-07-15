<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Trial Balance</h2>
        <x-document.export-bar title="Trial Balance — {{ $periodLabel }}" :printUrl="route('reports.trial-balance.print', ['period' => $period, 'from' => $dateFrom, 'to' => $dateTo])" />
    </div>

    <x-report-filter :period="$period" :dateFrom="$dateFrom" :dateTo="$dateTo" :branchId="$branchId" :branches="$this->getBranchesProperty()" />

    @php $tb = $this->trialBalance; @endphp

    @if (!$tb['balanced'])
        <div class="card p-4 bg-red-50 border-red-300 text-sm text-red-800 font-medium">
            ⚠ Trial Balance is NOT balanced. Difference: ৳{{ number_format(abs($tb['total_dr'] - $tb['total_cr']), 2) }}
        </div>
    @else
        <div class="card p-3 bg-green-50 border-green-200 text-sm text-green-700 font-medium">
            ✓ Trial Balance is balanced.
        </div>
    @endif

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th w-16">Code</th>
                        <th class="table-th">Account Name</th>
                        <th class="table-th">Type</th>
                        <th class="table-th text-right">Debit (৳)</th>
                        <th class="table-th text-right">Credit (৳)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $lastType = ''; @endphp
                    @foreach ($tb['rows'] as $row)
                        @if ($row['type'] !== $lastType)
                            <tr class="bg-indigo-50">
                                <td colspan="5"
                                    class="px-4 py-2 text-xs font-bold text-indigo-700 uppercase tracking-wider">
                                    {{ ucfirst($row['type']) }}s
                                </td>
                            </tr>
                            @php $lastType = $row['type']; @endphp
                        @endif
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-mono text-xs text-gray-400">{{ $row['code'] }}</td>
                            <td class="table-td text-gray-900 text-sm">{{ $row['name'] }}</td>
                            <td class="table-td text-xs text-gray-400 capitalize">{{ $row['type'] }}</td>
                            <td
                                class="table-td text-right {{ $row['debit_balance'] > 0 ? 'font-semibold text-gray-900' : 'text-gray-300' }}">
                                {{ $row['debit_balance'] > 0 ? '৳' . number_format($row['debit_balance'], 2) : '—' }}
                            </td>
                            <td
                                class="table-td text-right {{ $row['credit_balance'] > 0 ? 'font-semibold text-gray-900' : 'text-gray-300' }}">
                                {{ $row['credit_balance'] > 0 ? '৳' . number_format($row['credit_balance'], 2) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-900 text-white">
                    <tr>
                        <td colspan="3" class="px-4 py-3 font-bold">TOTAL</td>
                        <td class="px-4 py-3 text-right font-bold text-lg">৳{{ number_format($tb['total_dr'], 2) }}
                        </td>
                        <td class="px-4 py-3 text-right font-bold text-lg">৳{{ number_format($tb['total_cr'], 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
