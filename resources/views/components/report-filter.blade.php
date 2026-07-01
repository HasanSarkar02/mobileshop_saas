@props([
    'period' => 'this_month',
    'dateFrom' => '',
    'dateTo' => '',
    'branchId' => 0,
    'branches' => collect(),
    'showBranch' => true,
])

<div class="card p-4">
    <div class="flex flex-wrap items-end gap-3">
        {{-- Period Quick Buttons --}}
        <div>
            <label class="label text-xs mb-1.5">Period</label>
            <div class="flex flex-wrap gap-1">
                @foreach (\App\Reporting\Enums\ReportPeriod::cases() as $p)
                    @if ($p !== \App\Reporting\Enums\ReportPeriod::Custom)
                        <button wire:click="$set('period', '{{ $p->value }}')"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors
                                {{ $period === $p->value
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-white text-gray-600 border border-gray-200 hover:border-indigo-300' }}">
                            {{ $p->label() }}
                        </button>
                    @endif
                @endforeach
                <button wire:click="$set('period', 'custom')"
                    class="px-2.5 py-1.5 rounded-lg text-xs font-medium transition-colors
                        {{ $period === 'custom'
                            ? 'bg-indigo-600 text-white'
                            : 'bg-white text-gray-600 border border-gray-200 hover:border-indigo-300' }}">
                    Custom
                </button>
            </div>
        </div>

        {{-- Custom Date Range --}}
        @if ($period === 'custom')
            <div class="flex items-end gap-2">
                <div>
                    <label class="label text-xs">From</label>
                    <input wire:model.live="dateFrom" type="date" class="input text-sm w-36">
                </div>
                <span class="text-gray-400 text-sm pb-1">to</span>
                <div>
                    <label class="label text-xs">To</label>
                    <input wire:model.live="dateTo" type="date" class="input text-sm w-36">
                </div>
            </div>
        @endif

        {{-- Branch --}}
        @if ($showBranch && $branches->count() > 1)
            <div>
                <label class="label text-xs">Branch</label>
                <select wire:model.live="branchId" class="input text-sm w-40">
                    <option value="0">All Branches</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif

        {{-- Print --}}
        <div class="ml-auto">
            <button onclick="window.print()" class="btn-secondary btn-sm">
                🖨 Print
            </button>
        </div>
    </div>
</div>
