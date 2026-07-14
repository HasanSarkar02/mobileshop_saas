<div class="space-y-5">

    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Payroll Dashboard</h2>
        <div class="flex gap-2">
            <a href="{{ route('payroll.runs') }}" wire:navigate class="btn-secondary btn-sm">All Runs</a>
            @can('payroll.generate')
                <a href="{{ route('payroll.generate') }}" wire:navigate class="btn-primary">
                    + Generate Payroll
                </a>
            @endcan
        </div>
    </div>

    @php $stats = $this->stats; @endphp

    {{-- Pending Approvals Banner --}}
    @if($stats->pendingRuns > 0)
        <div class="card p-4 bg-amber-50 border-amber-300 flex items-center gap-4">
            <div class="flex-1 text-amber-800 font-medium">
                ⏳ {{ $stats->pendingRuns }} payroll run(s) awaiting your approval
            </div>
            @can('payroll.approve')
                <a href="{{ route('payroll.runs') }}?status=under_review" wire:navigate
                    class="btn-sm bg-amber-600 text-white hover:bg-amber-700 rounded-lg px-3 py-1.5 text-xs font-medium whitespace-nowrap">
                    Review Now →
                </a>
            @endcan
        </div>
    @endif

    {{-- KPI Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Current Month --}}
        <div class="card p-5 border-0 bg-indigo-50">
            <div class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-1">
                {{ now()->format('F Y') }}
            </div>
            @if($stats->currentRun)
                <div class="text-2xl font-bold text-indigo-800">
                    ৳{{ number_format($stats->currentRun->total_net_payable, 0) }}
                </div>
                <div class="mt-1">
                    <span class="badge {{ $stats->currentRun->status->badgeClass() }} text-xs">
                        {{ $stats->currentRun->status->label() }}
                    </span>
                </div>
            @else
                <div class="text-xl font-bold text-indigo-400">Not Generated</div>
                <a href="{{ route('payroll.generate') }}" wire:navigate
                    class="text-xs text-indigo-600 hover:underline mt-1 block">
                    Generate now →
                </a>
            @endif
        </div>
        <div class="card p-5 border-0 bg-red-50">
            <div class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">Outstanding Salary</div>
            <div class="text-2xl font-bold text-red-700">
                ৳{{ number_format($stats->outstandingSalary, 0) }}
            </div>
            <div class="text-xs text-red-400 mt-0.5">Approved, not yet paid</div>
        </div>
        <div class="card p-5 border-0 bg-amber-50">
            <div class="text-xs font-semibold text-amber-500 uppercase tracking-wider mb-1">Loan Outstanding</div>
            <div class="text-2xl font-bold text-amber-700">
                ৳{{ number_format($stats->activeLoans, 0) }}
            </div>
            <a href="{{ route('payroll.loans') }}" wire:navigate
                class="text-xs text-amber-600 hover:underline mt-0.5 block">
                View loans →
            </a>
        </div>
        <div class="card p-5 border-0 bg-gray-50">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Pending Approval</div>
            <div class="text-2xl font-bold text-gray-700">{{ $stats->pendingRuns }}</div>
            <div class="text-xs text-gray-400 mt-0.5">runs</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-5">

        {{-- Recent Payroll Runs --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">Recent Payroll Runs</h3>
                <a href="{{ route('payroll.runs') }}" wire:navigate
                    class="text-xs text-indigo-600 hover:underline">View all →</a>
            </div>
            @forelse($this->recentRuns as $run)
                <a href="{{ route('payroll.run.show', $run) }}" wire:navigate
                    class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 border-b border-gray-50 block"
                    wire:key="run-{{ $run->id }}">
                    <div>
                        <div class="font-semibold text-sm text-gray-900">
                            {{ $run->monthName() }}
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5 font-mono">
                            {{ $run->run_number }}
                            @if($run->branch) · {{ $run->branch->name }} @endif
                            · {{ $run->total_employees }} employees
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-gray-900">
                            ৳{{ number_format($run->total_net_payable, 0) }}
                        </div>
                        <span class="badge {{ $run->status->badgeClass() }} text-xs">
                            {{ $run->status->label() }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-gray-400 text-sm">
                    No payroll runs yet.
                    <a href="{{ route('payroll.generate') }}" wire:navigate class="text-indigo-600 hover:underline ml-1">
                        Generate first run →
                    </a>
                </div>
            @endforelse
        </div>

        {{-- Outstanding Salaries --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm">Outstanding Salaries</h3>
            </div>
            @forelse($this->outstandingSlips as $slip)
                <a href="{{ route('payroll.slip.show', $slip) }}" wire:navigate
                    class="flex items-center justify-between px-5 py-3 hover:bg-gray-50 border-b border-gray-50 block"
                    wire:key="slip-{{ $slip->id }}">
                    <div>
                        <div class="font-semibold text-sm text-gray-900">
                            {{ $slip->employee_name }}
                        </div>
                        <div class="text-xs text-gray-400 mt-0.5">
                            {{ $slip->payrollRun?->monthName() }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-red-600">
                            ৳{{ number_format($slip->balance_payable, 0) }}
                        </div>
                        <span class="badge {{ $slip->status->badgeClass() }} text-xs">
                            {{ $slip->status->label() }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center text-gray-400 text-sm">
                    🎉 No outstanding salaries.
                </div>
            @endforelse
        </div>
    </div>

    {{-- Quick Links Setup Section --}}
    <div class="card p-5">
        <h3 class="font-semibold text-gray-900 text-sm mb-3">Setup & Configuration</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach([
                ['route' => 'payroll.departments', 'label' => '🏢 Departments',  'can' => 'payroll.manage_departments'],
                ['route' => 'payroll.components',  'label' => '⚙ Components',    'can' => 'payroll.manage_components'],
                ['route' => 'payroll.policies',    'label' => '📋 Policies',      'can' => 'payroll.manage_components'],
                ['route' => 'payroll.loans',       'label' => '💳 Loans',          'can' => 'payroll.manage_loans'],
            ] as $link)
                @can($link['can'])
                    <a href="{{ route($link['route']) }}" wire:navigate
                        class="card p-3 text-center text-sm font-medium text-gray-700
                               hover:bg-indigo-50 hover:text-indigo-700 transition-colors">
                        {{ $link['label'] }}
                    </a>
                @endcan
            @endforeach
        </div>
    </div>
</div>