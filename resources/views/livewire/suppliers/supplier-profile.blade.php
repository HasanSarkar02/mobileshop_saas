<div class="max-w-5xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex flex-col sm:flex-row sm:items-start gap-4">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-900">{{ $supplier->name }}</h2>
            <div class="flex flex-wrap gap-3 text-sm text-gray-500 mt-1">
                @if ($supplier->phone)
                    <span>📞 {{ $supplier->phone }}</span>
                @endif
                @if ($supplier->email)
                    <span>✉ {{ $supplier->email }}</span>
                @endif
                @if ($supplier->contact_person)
                    <span>👤 {{ $supplier->contact_person }}</span>
                @endif
            </div>
            @if ($supplier->address)
                <div class="text-xs text-gray-400 mt-1">{{ $supplier->address }}</div>
            @endif
        </div>
        <div class="flex gap-2 shrink-0">
            <a href="{{ route('documents.supplier-statement', $supplier) }}" target="_blank" class="btn-secondary btn-sm">
                🖨 Print Statement
            </a>
            <a href="{{ route('suppliers.edit', $supplier) }}" wire:navigate class="btn-secondary btn-sm">
                ✏ Edit
            </a>
            <a href="{{ route('suppliers.index') }}" wire:navigate class="btn-secondary btn-sm">← Back</a>
        </div>
    </div>

    {{-- Balance Cards --}}
    {{-- Balance Cards --}}
    @php
        // Use ledger-calculated closing balance (always accurate)
        // rather than denormalized current_balance (may be stale)
        $ledgerData = $this->ledger;
        $calculatedBalance = $ledgerData->isNotEmpty() ? (float) $ledgerData->last()->running_balance : 0.0;
        $aging = $this->agingSummary;
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4 border-0 {{ $calculatedBalance > 0 ? 'bg-red-50' : 'bg-green-50' }}">
            <div
                class="text-xs font-semibold {{ $calculatedBalance > 0 ? 'text-red-500' : 'text-green-500' }} uppercase tracking-wider mb-1">
                Outstanding Balance
            </div>
            <div class="text-2xl font-bold {{ $calculatedBalance > 0 ? 'text-red-700' : 'text-green-700' }}">
                ৳{{ number_format($calculatedBalance, 2) }}
            </div>
            <div class="text-xs {{ $calculatedBalance > 0 ? 'text-red-400' : 'text-green-500' }} mt-0.5">
                {{ $calculatedBalance > 0 ? 'We owe supplier' : 'Fully paid ✓' }}
            </div>
        </div>
        <div class="card p-4 border-0 bg-gray-50">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Current</div>
            <div class="text-xl font-bold text-gray-800">৳{{ number_format($aging['current'], 0) }}</div>
        </div>
        <div class="card p-4 border-0 bg-amber-50">
            <div class="text-xs font-semibold text-amber-500 uppercase tracking-wider mb-1">1–60 Days</div>
            <div class="text-xl font-bold text-amber-800">৳{{ number_format($aging['1_30'] + $aging['31_60'], 0) }}
            </div>
        </div>
        <div class="card p-4 border-0 {{ $aging['61_90'] + $aging['over_90'] > 0 ? 'bg-red-50' : 'bg-gray-50' }}">
            <div class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-1">60+ Days</div>
            <div class="text-xl font-bold text-red-800">৳{{ number_format($aging['61_90'] + $aging['over_90'], 0) }}
            </div>
        </div>
    </div>

    {{-- Record Payment --}}
    @if ($calculatedBalance > 0)
        <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-900">Record Payment to Supplier</h3>
                <button wire:click="$toggle('showPaymentForm')" class="btn-primary btn-sm">
                    {{ $showPaymentForm ? '✕ Cancel' : '+ Record Payment' }}
                </button>
            </div>

            <div wire:show="showPaymentForm" class="space-y-4 pt-4 border-t border-gray-100">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-sm text-blue-800">
                    💡 Payment will post journal entry: <strong>Dr Accounts Payable → Cr Payment Account</strong>
                </div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="label text-xs">Amount (৳) *</label>
                        <input wire:model="payAmount" type="number" step="0.01" min="0.01"
                            max="{{ $calculatedBalance }}"
                            class="input text-sm font-semibold @error('payAmount') input-error @enderror"
                            placeholder="0.00">
                        <p class="text-xs text-gray-400 mt-0.5">
                            Outstanding: ৳{{ number_format($calculatedBalance, 2) }}
                        </p>
                        @error('payAmount')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">Pay From *</label>
                        <select wire:model="payAccountId"
                            class="input text-sm @error('payAccountId') input-error @enderror">
                            <option value="0">Select account…</option>
                            @foreach ($this->paymentAccounts as $pa)
                                <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                            @endforeach
                        </select>
                        @error('payAccountId')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">Payment Date *</label>
                        <input wire:model="payDate" type="date"
                            class="input text-sm @error('payDate') input-error @enderror">
                        @error('payDate')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">Payment Method</label>
                        <select wire:model="payMethod" class="input text-sm">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="mobile_banking">Mobile Banking (bKash/Nagad)</option>
                        </select>
                    </div>
                    <div>
                        <label class="label text-xs">Cheque / Bank Reference No.</label>
                        <input wire:model="payReference" type="text" class="input text-sm" placeholder="Optional">
                    </div>
                    <div>
                        <label class="label text-xs">Notes</label>
                        <input wire:model="payNotes" type="text" class="input text-sm" placeholder="Optional notes">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button wire:click="recordPayment" class="btn-primary btn-sm" wire:loading.attr="disabled"
                        wire:target="recordPayment">
                        <span wire:loading.remove wire:target="recordPayment">
                            Record Payment of ৳{{ number_format((float) ($payAmount ?: 0), 2) }}
                        </span>
                        <span wire:loading wire:target="recordPayment">Processing…</span>
                    </button>
                    <button wire:click="$set('showPaymentForm', false)" class="btn-secondary btn-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @else
        <div class="card p-4 bg-green-50 border-green-200">
            <p class="text-sm text-green-700 font-medium">✓ No outstanding balance — fully paid.</p>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="card overflow-hidden">
        <nav class="flex border-b border-gray-200 overflow-x-auto">
            @foreach ([['key' => 'overview', 'label' => 'Ledger'], ['key' => 'purchases', 'label' => 'Purchase History'], ['key' => 'payments', 'label' => 'Payment History'], ['key' => 'info', 'label' => 'Bank Details']] as $tab)
                <button wire:click="$set('activeTab', '{{ $tab['key'] }}')"
                    class="px-5 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                        {{ $activeTab === $tab['key'] ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>

        {{-- Ledger Tab --}}
        <div wire:show="activeTab === 'overview'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Date</th>
                        <th class="table-th">Type</th>
                        <th class="table-th">Reference</th>
                        <th class="table-th text-right">Debit (Purchased)</th>
                        <th class="table-th text-right">Credit (Paid/Returned)</th>
                        <th class="table-th text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->ledger as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td text-xs text-gray-500 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($row->txn_date)->format('d M Y') }}
                            </td>
                            <td class="table-td">
                                <span
                                    class="badge {{ $row->txn_type === 'Purchase' ? 'badge-red' : ($row->txn_type === 'Payment' ? 'badge-green' : 'badge-yellow') }} text-xs">
                                    {{ $row->txn_type }}
                                </span>
                            </td>
                            <td class="table-td text-xs text-gray-500">{{ $row->reference ?? '—' }}</td>
                            <td
                                class="table-td text-right {{ $row->debit > 0 ? 'text-red-600 font-semibold' : 'text-gray-300' }}">
                                {{ $row->debit > 0 ? '৳' . number_format($row->debit, 2) : '—' }}
                            </td>
                            <td
                                class="table-td text-right {{ $row->credit > 0 ? 'text-green-600 font-semibold' : 'text-gray-300' }}">
                                {{ $row->credit > 0 ? '৳' . number_format($row->credit, 2) : '—' }}
                            </td>
                            <td
                                class="table-td text-right font-bold {{ $row->running_balance > 0 ? 'text-red-700' : 'text-gray-400' }}">
                                ৳{{ number_format($row->running_balance, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-td text-center text-gray-400 py-8">
                                No transactions with this supplier yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($this->ledger->isNotEmpty())
                    <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                        <tr>
                            <td colspan="4" class="table-td font-bold">Closing Balance</td>
                            <td></td>
                            <td class="table-td text-right font-bold text-red-700 text-base">
                                ৳{{ number_format($supplier->current_balance, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        {{-- Purchase History --}}
        <div wire:show="activeTab === 'purchases'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Date</th>
                        <th class="table-th">Reference</th>
                        <th class="table-th">Branch</th>
                        <th class="table-th text-right">Amount</th>
                        <th class="table-th">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($purchases as $pur)
                        <tr class="hover:bg-gray-50 cursor-pointer"
                            wire:click="$navigate('{{ route('purchases.show', $pur) }}')">
                            <td class="table-td text-sm text-gray-500 whitespace-nowrap">
                                {{ $pur->purchase_date->format('d M Y') }}
                            </td>
                            <td class="table-td font-mono text-indigo-600 text-sm">{{ $pur->reference_number }}</td>
                            <td class="table-td text-gray-500 text-xs">{{ $pur->branch?->name }}</td>
                            <td class="table-td text-right font-bold">৳{{ number_format($pur->total_amount, 2) }}</td>
                            <td class="table-td">
                                <span
                                    class="badge {{ match ($pur->payment_status) {
                                        'paid' => 'badge-green',
                                        'partial' => 'badge-yellow',
                                        default => 'badge-red',
                                    } }} text-xs">
                                    {{ ucfirst($pur->payment_status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-td text-center text-gray-400 py-8">No purchases.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($purchases->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $purchases->links() }}</div>
            @endif
        </div>

        {{-- Payment History --}}
        <div wire:show="activeTab === 'payments'" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Date</th>
                        <th class="table-th">Ref No.</th>
                        <th class="table-th">Method</th>
                        <th class="table-th">Via</th>
                        <th class="table-th text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($payments as $pmt)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td text-sm text-gray-500 whitespace-nowrap">
                                {{ $pmt->payment_date->format('d M Y') }}
                            </td>
                            <td class="table-td font-mono text-indigo-600 text-sm">{{ $pmt->payment_number }}</td>
                            <td class="table-td text-xs text-gray-500 capitalize">
                                {{ str_replace('_', ' ', $pmt->payment_method) }}
                            </td>
                            <td class="table-td text-xs text-gray-500">{{ $pmt->paymentAccount?->name }}</td>
                            <td class="table-td text-right font-bold text-green-600">
                                ৳{{ number_format($pmt->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="table-td text-center text-gray-400 py-8">No payments recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($payments->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $payments->links() }}</div>
            @endif
        </div>

        {{-- Bank Details --}}
        <div wire:show="activeTab === 'info'" class="p-5">
            @if ($supplier->bank_account_number || $supplier->bank_name)
                <div class="space-y-2">
                    @foreach ([['label' => 'Bank Name', 'value' => $supplier->bank_name], ['label' => 'Account Number', 'value' => $supplier->bank_account_number], ['label' => 'Branch Name', 'value' => $supplier->bank_branch_name], ['label' => 'Routing Number', 'value' => $supplier->bank_routing_number], ['label' => 'Payment Terms', 'value' => $supplier->payment_terms], ['label' => 'Credit Limit', 'value' => $supplier->credit_limit > 0 ? '৳' . number_format($supplier->credit_limit, 2) : 'No limit']] as $row)
                        @if ($row['value'])
                            <div class="flex gap-4 text-sm py-2 border-b border-gray-50">
                                <span class="text-gray-400 w-36 shrink-0">{{ $row['label'] }}</span>
                                <span class="font-medium text-gray-800">{{ $row['value'] }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400">No bank details recorded.
                    <a href="{{ route('suppliers.edit', $supplier) }}" wire:navigate
                        class="text-indigo-600 hover:underline">Add details →</a>
                </p>
            @endif
        </div>
    </div>
</div>
