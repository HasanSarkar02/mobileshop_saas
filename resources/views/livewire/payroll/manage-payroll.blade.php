<div class="max-w-5xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex items-center gap-4">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-900">{{ $run->monthName() }} Payroll</h2>
            <div class="flex gap-3 mt-1">
                <span class="badge {{ $run->status->badgeClass() }}">{{ $run->status->label() }}</span>
            </div>
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-400">Net Payable</div>
            <div class="text-2xl font-bold text-indigo-700">৳{{ number_format($run->total_net, 2) }}</div>
        </div>
        <a href="{{ route('payroll.index') }}" wire:navigate class="btn-secondary btn-sm">← Back</a>
    </div>

    {{-- Items --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Employee</th>
                        <th class="table-th text-right">Gross</th>
                        <th class="table-th text-right">Bonus</th>
                        <th class="table-th text-right">Advance Ded.</th>
                        <th class="table-th text-right">Other Ded.</th>
                        <th class="table-th text-right">Net</th>
                        <th class="table-th">Account</th>
                        @if ($run->status->value !== 'paid')
                            <th class="table-th w-16">Save</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($run->items as $item)
                        @php
                            $net =
                                (float) $item->gross_salary +
                                (float) ($bonuses[$item->id] ?? 0) -
                                (float) $item->advance_deduction -
                                (float) ($deductions[$item->id] ?? 0);
                            $net = max(0, $net);
                        @endphp
                        <tr class="{{ $item->is_paid ? 'opacity-70' : '' }}" wire:key="pi-{{ $item->id }}">
                            <td class="table-td">
                                <div class="font-semibold text-sm text-gray-900">{{ $item->user?->name }}</div>
                                <div class="text-xs text-gray-400">
                                    {{ $item->user?->employeeProfile?->designation }}
                                </div>
                                @if ($item->is_paid)
                                    <span class="badge badge-green text-xs mt-0.5">Paid</span>
                                @endif
                            </td>
                            <td class="table-td text-right font-medium">৳{{ number_format($item->gross_salary, 2) }}
                            </td>
                            <td class="table-td text-right">
                                @if ($run->status->value !== 'paid')
                                    <input wire:model.lazy="bonuses.{{ $item->id }}" type="number" min="0"
                                        step="0.01"
                                        class="w-20 text-right text-sm border border-gray-200 rounded px-2 py-1 focus:ring-1 focus:ring-indigo-400">
                                @else
                                    ৳{{ number_format($item->bonus, 2) }}
                                @endif
                            </td>
                            <td class="table-td text-right text-red-500">
                                @if ($item->advance_deduction > 0)
                                    −৳{{ number_format($item->advance_deduction, 2) }}
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="table-td text-right">
                                @if ($run->status->value !== 'paid')
                                    <input wire:model.lazy="deductions.{{ $item->id }}" type="number"
                                        min="0" step="0.01"
                                        class="w-20 text-right text-sm border border-gray-200 rounded px-2 py-1 focus:ring-1 focus:ring-indigo-400">
                                @else
                                    @if ($item->other_deduction > 0)
                                        −৳{{ number_format($item->other_deduction, 2) }}
                                    @else
                                        —
                                    @endif
                                @endif
                            </td>
                            <td class="table-td text-right font-bold text-indigo-700">
                                ৳{{ number_format($run->status->value !== 'paid' ? $net : $item->net_salary, 2) }}
                            </td>
                            <td class="table-td text-xs text-gray-500">
                                @if ($run->status->value !== 'paid')
                                    <select wire:model="accountIds.{{ $item->id }}"
                                        class="input text-xs py-1 w-full">
                                        <option value="0">Default</option>
                                        @foreach ($this->paymentAccounts as $pa)
                                            <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    {{ $item->paymentAccount?->name ?? 'Default' }}
                                @endif
                            </td>
                            @if ($run->status->value !== 'paid')
                                <td class="table-td text-center">
                                    <button wire:click="updateItem({{ $item->id }})"
                                        class="text-xs text-green-600 hover:underline font-medium">
                                        Save
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td class="table-td font-bold">Total</td>
                        <td class="table-td text-right font-bold">৳{{ number_format($run->total_gross, 2) }}</td>
                        <td colspan="2"></td>
                        <td class="table-td text-right font-bold text-red-600">
                            @if ($run->total_deductions > 0)
                                −৳{{ number_format($run->total_deductions, 2) }}
                            @endif
                        </td>
                        <td class="table-td text-right font-bold text-indigo-700 text-base">
                            ৳{{ number_format($run->total_net, 2) }}
                        </td>
                        <td colspan="{{ $run->status->value !== 'paid' ? 2 : 1 }}"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
