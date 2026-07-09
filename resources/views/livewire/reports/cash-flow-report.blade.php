<div class="max-w-3xl mx-auto space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Cash Flow Statement</h2>
        <x-document.export-bar title="Cash Flow — {{ $periodLabel }}" :printUrl="route('reports.cash-flow.print', [
            'period' => $period,
            'from' => $dateFrom,
            'to' => $dateTo,
            'branch' => $branchId,
        ])" />
    </div>

    <x-report-filter :period="$period" :dateFrom="$dateFrom" :dateTo="$dateTo" :branchId="$branchId" :branches="$branches" />

    @php $cf = $this->cashFlow; @endphp

    {{-- Operating Activities --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 bg-indigo-700 text-white">
            <h3 class="font-bold">Cash Flow Statement</h3>
            <p class="text-indigo-200 text-sm">{{ $periodLabel }}</p>
        </div>

        {{-- Operating --}}
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">A. Operating Activities</span>
        </div>
        @foreach ([['label' => 'Cash Received from Sales', 'val' => $cf['sales_receipts'], 'positive' => true], ['label' => 'Less: Sales Returns (Refunds)', 'val' => -$cf['sales_returns'], 'positive' => false], ['label' => 'Cash Received from Service', 'val' => $cf['service_receipts'], 'positive' => true], ['label' => 'Cash Paid to Suppliers', 'val' => -$cf['supplier_paid'], 'positive' => false], ['label' => 'Cash Paid for Expenses', 'val' => -$cf['expense_paid'], 'positive' => false], ['label' => 'Cash Paid for Salaries', 'val' => -$cf['salary_paid'], 'positive' => false]] as $row)
            @if (abs($row['val']) > 0)
                <div class="px-5 py-2.5 flex items-center justify-between border-b border-gray-100 hover:bg-gray-50">
                    <span class="text-sm text-gray-700 pl-4">{{ $row['label'] }}</span>
                    <span class="text-sm font-semibold {{ $row['val'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                        {{ $row['val'] >= 0 ? '+' : '' }}৳{{ number_format($row['val'], 2) }}
                    </span>
                </div>
            @endif
        @endforeach
        <div class="px-5 py-3 flex justify-between border-b border-gray-200 bg-gray-50">
            <span class="font-semibold text-gray-800">Net Cash from Operating Activities</span>
            <span class="font-bold {{ $cf['net_operating'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                {{ $cf['net_operating'] >= 0 ? '+' : '' }}৳{{ number_format($cf['net_operating'], 2) }}
            </span>
        </div>

        {{-- Financing --}}
        <div class="px-5 py-3 bg-gray-50 border-b border-gray-200">
            <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">B. Financing Activities</span>
        </div>
        @foreach ([['label' => 'Owner / Partner Capital Injected', 'val' => $cf['capital_in'], 'positive' => true], ['label' => 'Owner / Partner Withdrawals', 'val' => -$cf['capital_out'], 'positive' => false], ['label' => 'Loans Received', 'val' => $cf['loans_in'], 'positive' => true], ['label' => 'Loan Repayments (incl. interest)', 'val' => -$cf['loans_out'], 'positive' => false]] as $row)
            @if (abs($row['val']) > 0)
                <div class="px-5 py-2.5 flex items-center justify-between border-b border-gray-100 hover:bg-gray-50">
                    <span class="text-sm text-gray-700 pl-4">{{ $row['label'] }}</span>
                    <span class="text-sm font-semibold {{ $row['val'] >= 0 ? 'text-green-700' : 'text-red-600' }}">
                        {{ $row['val'] >= 0 ? '+' : '' }}৳{{ number_format($row['val'], 2) }}
                    </span>
                </div>
            @endif
        @endforeach
        <div class="px-5 py-3 flex justify-between border-b border-gray-200 bg-gray-50">
            <span class="font-semibold text-gray-800">Net Cash from Financing Activities</span>
            <span class="font-bold {{ $cf['net_financing'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                {{ $cf['net_financing'] >= 0 ? '+' : '' }}৳{{ number_format($cf['net_financing'], 2) }}
            </span>
        </div>

        {{-- Net Change --}}
        <div class="px-5 py-4 flex items-center justify-between border-b border-gray-200">
            <span class="font-semibold text-gray-800">C. Net Change in Cash (A + B)</span>
            <span class="font-bold text-lg {{ $cf['net_change'] >= 0 ? 'text-green-700' : 'text-red-700' }}">
                {{ $cf['net_change'] >= 0 ? '+' : '' }}৳{{ number_format($cf['net_change'], 2) }}
            </span>
        </div>

        {{-- Balances --}}
        <div class="px-5 py-3 flex justify-between border-b border-gray-100">
            <span class="text-gray-600">Opening Cash Balance</span>
            <span class="font-semibold">৳{{ number_format($cf['opening_balance'], 2) }}</span>
        </div>
        <div
            class="px-5 py-4 flex justify-between {{ $cf['closing_balance'] >= 0 ? 'bg-green-700' : 'bg-red-700' }} text-white">
            <span class="font-bold text-lg">Closing Cash Balance</span>
            <span class="font-bold text-xl">৳{{ number_format($cf['closing_balance'], 2) }}</span>
        </div>

        <div class="px-5 py-3">
            <p class="text-xs text-gray-400">
                This statement uses the <strong>direct method</strong>.
                Operating activities derived from transaction journals.
                Financing activities from Treasury module records.
                Internal transfers between accounts are excluded to avoid double-counting.
            </p>
        </div>
    </div>
</div>
