<div class="max-w-4xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex flex-col sm:flex-row sm:items-start gap-4">
        <div class="flex-1">
            <div class="font-mono font-bold text-indigo-700 text-xl">{{ $ticket->ticket_number }}</div>
            <h2 class="text-lg font-bold text-gray-900 mt-1">{{ $ticket->customer_name }}</h2>
            <div class="text-sm text-gray-500 mt-0.5">{{ $ticket->customer_phone }}</div>
            <div class="flex flex-wrap gap-2 mt-2">
                <span class="badge {{ $ticket->status->badgeClass() }}">{{ $ticket->status->label() }}</span>
                @if ($ticket->is_warranty_service)
                    <span class="badge badge-blue">Warranty Service</span>
                @endif
                @if ($ticket->technician)
                    <span class="badge badge-gray">👨‍🔧 {{ $ticket->technician->name }}</span>
                @endif
            </div>
        </div>
        <div class="flex flex-col gap-2 shrink-0">
            @if ($ticket->isEditable())
                <a href="{{ route('service.edit', $ticket) }}" wire:navigate class="btn-secondary btn-sm">
                    Edit Ticket
                </a>
            @endif
            <a href="{{ route('service.index') }}" wire:navigate class="btn-secondary btn-sm">← Back</a>
        </div>
    </div>

    {{-- Financial Summary --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 border-0 bg-indigo-50">
            <div class="text-xl font-bold text-indigo-700">৳{{ number_format($ticket->total_charge, 2) }}</div>
            <div class="text-xs font-medium text-indigo-500 mt-0.5">Total Charge</div>
        </div>
        <div class="card p-4 border-0 bg-green-50">
            <div class="text-xl font-bold text-green-700">৳{{ number_format($ticket->amount_paid, 2) }}</div>
            <div class="text-xs font-medium text-green-500 mt-0.5">Amount Paid</div>
        </div>
        <div class="card p-4 border-0 {{ $ticket->amount_due > 0 ? 'bg-red-50' : 'bg-gray-50' }}">
            <div class="text-xl font-bold {{ $ticket->amount_due > 0 ? 'text-red-700' : 'text-gray-400' }}">
                ৳{{ number_format($ticket->amount_due, 2) }}
            </div>
            <div class="text-xs font-medium {{ $ticket->amount_due > 0 ? 'text-red-500' : 'text-gray-400' }} mt-0.5">
                Balance Due
            </div>
        </div>
    </div>

    {{-- Status Workflow --}}
    @if ($ticket->isEditable())
        <div class="card p-5">
            <h3 class="font-semibold text-gray-900 text-sm mb-3">Update Status</h3>
            <div class="flex items-center gap-3 flex-wrap">
                @foreach ($ticket->status->nextStatuses() as $nextStatus)
                    <div class="flex items-center gap-2">
                        @if (
                            $nextStatus === \App\Enums\ServiceTicketStatus::Delivered &&
                                $ticket->parts->where('from_inventory', true)->isNotEmpty())
                            <label class="flex items-center gap-1.5 text-xs text-gray-500">
                                <input wire:model="showDeductParts" type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600">
                                Deduct inventory parts
                            </label>
                        @endif
                        <button wire:click="$set('newStatus', '{{ $nextStatus->value }}'); updateStatus()"
                            class="btn-sm {{ $nextStatus === \App\Enums\ServiceTicketStatus::Cancelled ? 'btn-danger' : 'btn-primary' }}">
                            → {{ $nextStatus->label() }}
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Device + Problem --}}
    <div class="grid sm:grid-cols-2 gap-5">
        <div class="card p-5 space-y-2">
            <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">Device</h3>
            @foreach ([['label' => 'Brand', 'value' => $ticket->device_brand], ['label' => 'Model', 'value' => $ticket->device_model], ['label' => 'IMEI', 'value' => $ticket->device_imei], ['label' => 'Color', 'value' => $ticket->device_color], ['label' => 'Condition', 'value' => $ticket->device_condition]] as $row)
                @if ($row['value'])
                    <div class="flex gap-3 text-sm">
                        <span class="text-gray-400 w-20 shrink-0">{{ $row['label'] }}</span>
                        <span
                            class="font-medium text-gray-800 {{ $row['label'] === 'IMEI' ? 'font-mono text-indigo-600' : '' }}">
                            {{ $row['value'] }}
                        </span>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="card p-5 space-y-3">
            <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">Problem</h3>
            <div class="text-sm text-gray-700">{{ $ticket->problem_description }}</div>
            @if ($ticket->diagnosis_notes)
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 text-xs text-blue-800">
                    <strong>Diagnosis:</strong> {{ $ticket->diagnosis_notes }}
                </div>
            @endif
        </div>
    </div>

    {{-- Parts --}}
    @if ($ticket->parts->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm">Parts Used</h3>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Description</th>
                        <th class="table-th text-center">Qty</th>
                        <th class="table-th text-right">Unit Cost</th>
                        <th class="table-th text-right">Total</th>
                        <th class="table-th">Source</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($ticket->parts as $part)
                        <tr>
                            <td class="table-td font-medium text-sm">{{ $part->part_description }}</td>
                            <td class="table-td text-center">{{ $part->quantity }}</td>
                            <td class="table-td text-right">৳{{ number_format($part->unit_cost, 2) }}</td>
                            <td class="table-td text-right font-semibold">৳{{ number_format($part->line_total, 2) }}
                            </td>
                            <td class="table-td">
                                @if ($part->from_inventory)
                                    <span class="badge badge-blue text-xs">Inventory</span>
                                @else
                                    <span class="badge badge-gray text-xs">External</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-gray-50">
                        <td colspan="3" class="table-td text-right font-semibold text-gray-600">Parts Total</td>
                        <td class="table-td text-right font-bold">৳{{ number_format($ticket->parts_cost, 2) }}</td>
                        <td></td>
                    </tr>
                    <tr class="bg-gray-50">
                        <td colspan="3" class="table-td text-right font-semibold text-gray-600">Labor Charge</td>
                        <td class="table-td text-right font-bold">৳{{ number_format($ticket->labor_charge, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endif

    {{-- Collect Payment --}}
    @if ($ticket->amount_due > 0 && !$ticket->status->isTerminal())
        <div class="card p-5">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-gray-900 text-sm">Collect Payment</h3>
                <button wire:click="$toggle('showPayForm')" class="btn-success btn-sm">
                    + Collect ৳{{ number_format($ticket->amount_due, 2) }}
                </button>
            </div>

            <div wire:show="showPayForm" class="space-y-4 mt-4 pt-4 border-t border-gray-100">
                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label text-xs">Amount *</label>
                        <input wire:model="payAmount" type="number" step="0.01" min="0.01"
                            max="{{ $ticket->amount_due }}"
                            class="input text-sm font-semibold @error('payAmount') input-error @enderror">
                        @error('payAmount')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">Received Via *</label>
                        <select wire:model="payAccountId"
                            class="input text-sm @error('payAccountId') input-error @enderror">
                            <option value="0">Select…</option>
                            @foreach ($this->paymentAccounts as $pa)
                                <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                            @endforeach
                        </select>
                        @error('payAccountId')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">Date *</label>
                        <input wire:model="payDate" type="date" class="input text-sm">
                        @error('payDate')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="flex gap-3">
                    <button wire:click="recordPayment" class="btn-success btn-sm" wire:loading.attr="disabled">
                        Record Payment
                    </button>
                    <button wire:click="$set('showPayForm', false)" class="btn-secondary btn-sm">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Payment History --}}
    @if ($ticket->payments->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm">Payment History</h3>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Date</th>
                        <th class="table-th text-right">Amount</th>
                        <th class="table-th">Via</th>
                        <th class="table-th">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($ticket->payments as $pmt)
                        <tr>
                            <td class="table-td text-sm text-gray-500">{{ $pmt->payment_date->format('d M Y') }}</td>
                            <td class="table-td text-right font-bold text-green-700">
                                ৳{{ number_format($pmt->amount, 2) }}</td>
                            <td class="table-td text-sm text-gray-600">{{ $pmt->paymentAccount?->name }}</td>
                            <td class="table-td text-xs text-gray-400">{{ $pmt->notes ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Timestamps --}}
    <div class="text-xs text-gray-400 text-center pb-4 space-y-1">
        <div>Received: {{ $ticket->received_at?->format('d M Y H:i') }}</div>
        @if ($ticket->ready_at)
            <div>Ready: {{ $ticket->ready_at->format('d M Y H:i') }}</div>
        @endif
        @if ($ticket->delivered_at)
            <div>Delivered: {{ $ticket->delivered_at->format('d M Y H:i') }}</div>
        @endif
    </div>
</div>
