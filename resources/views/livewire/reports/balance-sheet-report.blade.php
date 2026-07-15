<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Balance Sheet</h2>
        <x-document.export-bar title="Balance Sheet — {{ $periodLabel }}" :printUrl="route('reports.balance-sheet.print', ['period' => $period, 'from' => $dateFrom, 'to' => $dateTo])" />
    </div>

    <x-report-filter :period="$period" :dateFrom="$dateFrom" :dateTo="$dateTo" :branchId="$branchId" :branches="$this->getBranchesProperty()" />

    @php $bs = $this->balanceSheet; @endphp

    @php
        $checkBalance = abs($bs['totalAssets'] - $bs['totalLiabilities'] - $bs['totalEquity']);
    @endphp
    @if ($checkBalance > 0.01)
        <div class="card p-3 bg-red-50 border-red-200 text-sm text-red-700">
            ⚠ Balance Sheet does not balance. Difference: ৳{{ number_format($checkBalance, 2) }}
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-5">

        {{-- Assets --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 bg-blue-700 text-white">
                <h3 class="font-bold">ASSETS</h3>
            </div>
            <table class="w-full">
                <tbody class="divide-y divide-gray-50">
                    @foreach ($bs['assets'] as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-mono text-xs text-gray-400">{{ $row['code'] }}</td>
                            <td class="px-4 py-2 text-sm text-gray-800">{{ $row['name'] }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-gray-900">
                                ৳{{ number_format($row['balance'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-blue-50 border-t-2 border-blue-300">
                    <tr>
                        <td colspan="2" class="px-4 py-3 font-bold text-blue-900">Total Assets</td>
                        <td class="px-4 py-3 text-right font-bold text-blue-800 text-lg">
                            ৳{{ number_format($bs['totalAssets'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Liabilities + Equity --}}
        <div class="space-y-5">
            <div class="card overflow-hidden">
                <div class="px-5 py-3 bg-red-700 text-white">
                    <h3 class="font-bold">LIABILITIES</h3>
                </div>
                <table class="w-full">
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($bs['liabilities'] as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-mono text-xs text-gray-400">{{ $row['code'] }}</td>
                                <td class="px-4 py-2 text-sm text-gray-800">{{ $row['name'] }}</td>
                                <td class="px-4 py-2 text-right font-semibold text-red-600">
                                    ৳{{ number_format($row['balance'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-red-50 border-t-2 border-red-200">
                        <tr>
                            <td colspan="2" class="px-4 py-3 font-bold text-red-900">Total Liabilities</td>
                            <td class="px-4 py-3 text-right font-bold text-red-800">
                                ৳{{ number_format($bs['totalLiabilities'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="card overflow-hidden">
                <div class="px-5 py-3 bg-green-700 text-white">
                    <h3 class="font-bold">EQUITY</h3>
                </div>
                <table class="w-full">
                    <tbody class="divide-y divide-gray-50">
                        @foreach ($bs['equity'] as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-mono text-xs text-gray-400">{{ $row['code'] }}</td>
                                <td class="px-4 py-2 text-sm text-gray-800">{{ $row['name'] }}</td>
                                <td
                                    class="px-4 py-2 text-right font-semibold {{ $row['balance'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                                    {{ $row['balance'] >= 0 ? '+' : '' }}৳{{ number_format($row['balance'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-green-50 border-t-2 border-green-200">
                        <tr>
                            <td colspan="2" class="px-4 py-3 font-bold text-green-900">Total Equity</td>
                            <td class="px-4 py-3 text-right font-bold text-green-800">
                                ৳{{ number_format($bs['totalEquity'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="card p-4 bg-gray-900 text-white flex items-center justify-between">
                <span class="font-bold">Total Liabilities + Equity</span>
                <span
                    class="font-bold text-xl">৳{{ number_format($bs['totalLiabilities'] + $bs['totalEquity'], 2) }}</span>
            </div>
        </div>
    </div>
</div>
