<div class="space-y-5">

    {{-- ── Quick Salary Draw ── --}}
    <div class="card p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-gray-900">Salary Draw
                    <span class="text-sm font-normal text-gray-500 ml-1">
                        — Record cash given to an employee anytime during the month
                    </span>
                </h3>
            </div>
            <button wire:click="openDrawForm()" class="btn-success btn-sm">+ Record Draw</button>
        </div>

        {{-- Current Month Summary --}}
        @if ($this->currentMonthDraws->isNotEmpty())
            <div class="space-y-3">
                <div class="text-xs text-gray-400 font-semibold uppercase tracking-wider">
                    {{ now()->format('F Y') }} — Draws So Far
                </div>
                @foreach ($this->currentMonthDraws as $userId => $draws)
                    @php
                        $emp = $draws->first()->user;
                        $profile = $emp?->employeeProfile;
                        $total = $draws->sum('amount');
                        $gross = $profile?->grossSalary() ?? 0;
                        $pct = $gross > 0 ? min(100, round(($total / $gross) * 100)) : 0;
                    @endphp
                    <div class="flex items-center gap-4">
                        <div class="w-32 font-medium text-sm text-gray-900 truncate">
                            {{ $emp?->name }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="flex-1 bg-gray-100 rounded-full h-2">
                                    <div class="bg-indigo-500 h-2 rounded-full transition-all"
                                        style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 w-32 text-right">
                                    ৳{{ number_format($total, 0) }} / ৳{{ number_format($gross, 0) }}
                                </span>
                            </div>
                        </div>
                        <button wire:click="openDrawForm({{ $userId }})"
                            class="text-xs text-indigo-600 hover:underline font-medium shrink-0">
                            + Give More
                        </button>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400">No draws recorded for {{ now()->format('F Y') }} yet.</p>
        @endif
    </div>

    {{-- ── Draw Form ── --}}
    <div wire:show="showDrawForm" class="card p-5 border-green-200 bg-green-50">
        <h4 class="font-semibold text-green-900 mb-4">Record Salary Draw / Payment</h4>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="label text-xs">Employee *</label>
                <select wire:model="drawEmployeeId" class="input text-sm">
                    <option value="0">Select employee…</option>
                    @foreach ($this->employees as $emp)
                        <option value="{{ $emp->id }}">
                            {{ $emp->name }}
                            @if ($emp->employeeProfile)
                                (৳{{ number_format($emp->employeeProfile->grossSalary(), 0) }}/month)
                            @endif
                        </option>
                    @endforeach
                </select>
                @error('drawEmployeeId')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label text-xs">Amount (৳) *</label>
                <input wire:model="drawAmount" type="number" min="1" step="0.01"
                    class="input text-sm font-semibold @error('drawAmount') input-error @enderror" placeholder="0">
                @error('drawAmount')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label text-xs">Type</label>
                <select wire:model="drawType" class="input text-sm">
                    <option value="salary">Salary Draw (counts toward this month)</option>
                    <option value="bonus">Bonus (extra, not deducted from salary)</option>
                    <option value="advance">Advance (next month deduction)</option>
                </select>
            </div>
            <div>
                <label class="label text-xs">Pay From *</label>
                <select wire:model="drawPayAccId" class="input text-sm @error('drawPayAccId') input-error @enderror">
                    <option value="0">Select account…</option>
                    @foreach ($this->paymentAccounts as $pa)
                        <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                    @endforeach
                </select>
                @error('drawPayAccId')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label text-xs">Date *</label>
                <input wire:model="drawDate" type="date"
                    class="input text-sm @error('drawDate') input-error @enderror">
                @error('drawDate')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label text-xs">For Month *</label>
                <div class="flex gap-2">
                    <select wire:model="drawMonth" class="input text-sm">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endforeach
                    </select>
                    <input wire:model="drawYear" type="number" min="2020" class="input text-sm w-24">
                </div>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <label class="label text-xs">Notes</label>
                <input wire:model="drawNotes" type="text" class="input text-sm" placeholder="Optional note…">
            </div>
        </div>
        <div class="flex gap-2 mt-4">
            <button wire:click="recordDraw" wire:loading.attr="disabled" class="btn-success btn-sm">
                <span wire:loading.remove>Record Draw</span>
                <span wire:loading>Saving…</span>
            </button>
            <button wire:click="$set('showDrawForm', false)" class="btn-secondary btn-sm">Cancel</button>
        </div>
    </div>

    {{-- ── Month-End Payroll ── --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-gray-900">Month-End Payroll Settlement</h3>
                <p class="text-xs text-gray-400 mt-0.5">
                    Generate at month-end to settle remaining salary (gross minus already drawn).
                </p>
            </div>
            <button wire:click="$toggle('showGenerate')" class="btn-primary btn-sm">+ Generate</button>
        </div>

        <div wire:show="showGenerate" class="px-5 py-4 bg-indigo-50 border-b border-indigo-200">
            <div class="flex items-end gap-4 flex-wrap">
                <div>
                    <label class="label text-xs">Year</label>
                    <input wire:model="generateYear" type="number" class="input w-24 text-sm">
                </div>
                <div>
                    <label class="label text-xs">Month</label>
                    <select wire:model="generateMonth" class="input text-sm w-36">
                        @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button wire:click="generate" wire:loading.attr="disabled" class="btn-primary btn-sm">
                        <span wire:loading.remove>Generate Draft</span>
                        <span wire:loading>Generating…</span>
                    </button>
                    <button wire:click="$set('showGenerate', false)" class="btn-secondary btn-sm">Cancel</button>
                </div>
            </div>
            <p class="text-xs text-indigo-600 mt-2">
                💡 This calculates: Gross Salary − Draws this month = Remaining to pay
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Month</th>
                        <th class="table-th">Employees</th>
                        <th class="table-th text-right">Gross</th>
                        <th class="table-th text-right">Already Drawn</th>
                        <th class="table-th text-right">Remaining (Net)</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($runs as $run)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td font-semibold text-gray-900">{{ $run->monthName() }}</td>
                            <td class="table-td text-gray-500">{{ $run->items->count() }}</td>
                            <td class="table-td text-right">৳{{ number_format($run->total_gross, 0) }}</td>
                            <td class="table-td text-right text-amber-600">
                                ৳{{ number_format($run->total_deductions, 0) }}
                            </td>
                            <td class="table-td text-right font-bold text-indigo-700">
                                ৳{{ number_format($run->total_net, 0) }}
                            </td>
                            <td class="table-td">
                                <span
                                    class="badge {{ $run->status->badgeClass() }}">{{ $run->status->label() }}</span>
                            </td>
                            <td class="table-td">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('documents.payroll', $run) }}" target="_blank"
                                        class="text-xs text-indigo-600 hover:underline font-medium">
                                        🖨 Print
                                    </a>
                                    @if ($run->status === \App\Enums\PayrollStatus::Draft)
                                        <button wire:click="approve({{ $run->id }})"
                                            wire:confirm="Approve payroll for {{ $run->monthName() }}?"
                                            class="text-xs text-green-600 hover:underline font-medium">
                                            Approve
                                        </button>
                                        <button wire:click="deleteDraft({{ $run->id }})"
                                            wire:confirm="Delete this draft? Draws will be unlinked and can be re-used."
                                            class="text-xs text-red-500 hover:underline font-medium">
                                            Delete
                                        </button>
                                    @elseif($run->status === \App\Enums\PayrollStatus::Approved)
                                        <button wire:click="openPayModal({{ $run->id }})"
                                            class="text-xs text-indigo-600 hover:underline font-medium">
                                            Pay Remaining
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-td text-center text-gray-400 py-10">
                                No payrolls yet. Generate month-end payroll above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($runs->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $runs->links() }}</div>
        @endif
    </div>

    {{-- Pay Modal --}}
    @if ($showPayModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Pay Remaining Salary</h3>
                <p class="text-sm text-gray-500">
                    This pays the REMAINING amount after deducting draws already given.
                    Select the default payment account.
                </p>
                <div>
                    <label class="label">Default Payment Account *</label>
                    <select wire:model="defaultPayAccId"
                        class="input @error('defaultPayAccId') input-error @enderror">
                        <option value="0">Select…</option>
                        @foreach ($this->paymentAccounts as $pa)
                            <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                        @endforeach
                    </select>
                    @error('defaultPayAccId')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button wire:click="pay" class="btn-primary flex-1" wire:loading.attr="disabled">
                        <span wire:loading.remove>Confirm Payment</span>
                        <span wire:loading>Processing…</span>
                    </button>
                    <button wire:click="$set('showPayModal', false)" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
