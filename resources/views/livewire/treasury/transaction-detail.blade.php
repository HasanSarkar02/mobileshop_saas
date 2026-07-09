@php
    $txn = $transaction;
    $isCompleted = $txn->status === \App\Enums\TreasuryTransactionStatus::Completed;
    $isPending = $txn->status === \App\Enums\TreasuryTransactionStatus::PendingApproval;
    $isReversed = $txn->status === \App\Enums\TreasuryTransactionStatus::Reversed;
@endphp

<div class="max-w-3xl mx-auto space-y-5">

    {{-- Header --}}
    <div class="card p-5 flex flex-col sm:flex-row sm:items-start gap-4">
        <div class="flex-1">
            <div class="font-mono font-bold text-indigo-700 text-xl">{{ $txn->transaction_number }}</div>
            <h2 class="text-lg font-bold text-gray-900 mt-1">{{ $txn->typeIcon() }} {{ $txn->typeLabel() }}</h2>
            <div class="flex flex-wrap gap-2 mt-2">
                <span class="badge {{ $txn->status->badgeClass() }}">{{ $txn->status->label() }}</span>
                <span class="badge {{ $txn->transaction_category->badgeClass() }}">
                    {{ $txn->transaction_category->label() }}
                </span>
            </div>
        </div>
        <div class="flex flex-col gap-2 shrink-0">
            {{-- Owner actions --}}
            @if ($isPending && auth()->user()->isOwner())
                <button wire:click="approve" wire:confirm="Approve and post journal for {{ $txn->transaction_number }}?"
                    class="btn-success btn-sm">
                    ✓ Approve & Post
                </button>
                <button wire:click="$set('showRejectModal', true)" class="btn-danger btn-sm">
                    ✗ Reject
                </button>
            @endif
            @if ($isCompleted && auth()->user()->can('treasury.reverse') && $txn->isReversible())
                <button wire:click="$set('showReverseModal', true)" class="btn-secondary btn-sm">
                    ↩ Reverse
                </button>
            @endif
            <a href="{{ route('treasury.index') }}" wire:navigate class="btn-secondary btn-sm">← Back</a>
        </div>
    </div>

    {{-- Transaction Summary --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 border-0 bg-gray-50">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Gross Amount</div>
            <div class="text-xl font-bold text-gray-900">৳{{ number_format($txn->amount, 2) }}</div>
        </div>
        <div class="card p-4 border-0 {{ $txn->fee_amount > 0 ? 'bg-red-50' : 'bg-gray-50' }}">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Fee / Interest</div>
            <div class="text-xl font-bold {{ $txn->fee_amount > 0 ? 'text-red-700' : 'text-gray-400' }}">
                {{ $txn->fee_amount > 0 ? '৳' . number_format($txn->fee_amount, 2) : 'None' }}
            </div>
        </div>
        <div class="card p-4 border-0 bg-indigo-50">
            <div class="text-xs font-semibold text-indigo-500 uppercase tracking-wider mb-1">Net Amount</div>
            <div class="text-xl font-bold text-indigo-700">৳{{ number_format($txn->net_amount, 2) }}</div>
        </div>
    </div>

    {{-- Details --}}
    <div class="grid sm:grid-cols-2 gap-5">
        <div class="card p-5 space-y-2">
            <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">Transaction Details</h3>
            @foreach ([['label' => 'Date', 'value' => $txn->transaction_date->format('d M Y')], ['label' => 'Description', 'value' => $txn->description], ['label' => 'Reference', 'value' => $txn->reference_number ?? '—'], ['label' => 'Branch', 'value' => $txn->branch?->name], ['label' => 'Created By', 'value' => $txn->createdBy?->name], ['label' => 'Created At', 'value' => $txn->created_at->format('d M Y H:i')]] as $row)
                @if ($row['value'])
                    <div class="flex gap-3 text-sm">
                        <span class="text-gray-400 w-24 shrink-0">{{ $row['label'] }}</span>
                        <span class="text-gray-800 font-medium">{{ $row['value'] }}</span>
                    </div>
                @endif
            @endforeach
        </div>

        <div class="card p-5 space-y-2">
            <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">Account Flow</h3>
            @if ($txn->fromAccount)
                <div class="flex gap-3 text-sm">
                    <span class="text-gray-400 w-24 shrink-0">From</span>
                    <span class="font-medium text-red-700">{{ $txn->fromAccount->name }}</span>
                </div>
            @endif
            @if ($txn->toAccount)
                <div class="flex gap-3 text-sm">
                    <span class="text-gray-400 w-24 shrink-0">To</span>
                    <span class="font-medium text-green-700">{{ $txn->toAccount->name }}</span>
                </div>
            @endif
            @if ($txn->third_party_name)
                <div class="flex gap-3 text-sm">
                    <span class="text-gray-400 w-24 shrink-0">Third Party</span>
                    <span class="font-medium text-gray-800">{{ $txn->third_party_name }}</span>
                </div>
            @endif
            @if ($txn->third_party_reference)
                <div class="flex gap-3 text-sm">
                    <span class="text-gray-400 w-24 shrink-0">Their Ref</span>
                    <span class="font-medium text-gray-800">{{ $txn->third_party_reference }}</span>
                </div>
            @endif
            @if ($txn->notes)
                <div class="bg-gray-50 rounded-lg p-2 text-xs text-gray-600 mt-2">{{ $txn->notes }}</div>
            @endif
        </div>
    </div>

    {{-- Approval Status Timeline --}}
    <div class="card p-5">
        <h3 class="font-semibold text-gray-900 text-sm mb-4">Status Timeline</h3>
        <div class="space-y-3">
            <div class="flex items-center gap-3 text-sm">
                <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                    <span class="text-green-600 text-xs">✓</span>
                </div>
                <div>
                    <span class="font-medium">Created</span> by {{ $txn->createdBy?->name }}
                    <span class="text-gray-400 ml-2">{{ $txn->created_at->format('d M Y H:i') }}</span>
                </div>
            </div>

            @if ($isPending)
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                        <span class="text-amber-600 text-xs">⏳</span>
                    </div>
                    <div class="text-amber-700 font-medium">Awaiting Owner Approval</div>
                </div>
            @endif

            @if ($txn->approved_at)
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-6 h-6 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                        <span class="text-green-600 text-xs">✓</span>
                    </div>
                    <div>
                        <span class="font-medium">Approved & Journal Posted</span>
                        by {{ $txn->approvedBy?->name }}
                        <span class="text-gray-400 ml-2">{{ $txn->approved_at->format('d M Y H:i') }}</span>
                    </div>
                </div>
            @endif

            @if ($txn->rejected_at)
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                        <span class="text-red-600 text-xs">✗</span>
                    </div>
                    <div>
                        <span class="font-medium text-red-700">Rejected</span>
                        by {{ $txn->rejectedBy?->name }}
                        <span class="text-gray-400 ml-2">{{ $txn->rejected_at->format('d M Y H:i') }}</span>
                        <div class="text-xs text-red-500 mt-0.5">Reason: {{ $txn->rejection_reason }}</div>
                    </div>
                </div>
            @endif

            @if ($txn->reversed_at)
                <div class="flex items-center gap-3 text-sm">
                    <div class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center shrink-0">
                        <span class="text-gray-500 text-xs">↩</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Reversed</span>
                        <span class="text-gray-400 ml-2">{{ $txn->reversed_at->format('d M Y H:i') }}</span>
                        <div class="text-xs text-gray-500 mt-0.5">Reason: {{ $txn->reversal_reason }}</div>
                        @if ($txn->reversedBy)
                            <a href="{{ route('treasury.show', $txn->reversedBy) }}" wire:navigate
                                class="text-xs text-indigo-600 hover:underline">
                                Reversal: {{ $txn->reversedBy->transaction_number }}
                            </a>
                        @endif
                    </div>
                </div>
            @endif

            @if ($txn->reversalOf)
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-2 text-xs text-amber-800">
                    This is a reversal of
                    <a href="{{ route('treasury.show', $txn->reversalOf) }}" wire:navigate
                        class="font-mono font-semibold hover:underline">
                        {{ $txn->reversalOf->transaction_number }}
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Journal Entry --}}
    @if ($txn->journalEntry)
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 text-sm">
                    Journal Entry
                    <span class="font-mono text-indigo-600 ml-2">{{ $txn->journalEntry->entry_number ?? '' }}</span>
                </h3>
                <span class="text-xs text-gray-400">
                    {{ $txn->journalEntry->entry_date }}
                </span>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Account</th>
                        <th class="table-th">Code</th>
                        <th class="table-th">Description</th>
                        <th class="table-th text-right">Debit (৳)</th>
                        <th class="table-th text-right">Credit (৳)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($txn->journalEntry->lines as $line)
                        <tr>
                            <td class="table-td font-medium text-sm text-gray-900">{{ $line->account?->name }}</td>
                            <td class="table-td font-mono text-xs text-gray-500">{{ $line->account?->code }}</td>
                            <td class="table-td text-xs text-gray-500">{{ $line->description }}</td>
                            <td
                                class="table-td text-right font-semibold {{ $line->debit > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                                {{ $line->debit > 0 ? '৳' . number_format($line->debit, 2) : '—' }}
                            </td>
                            <td
                                class="table-td text-right font-semibold {{ $line->credit > 0 ? 'text-gray-900' : 'text-gray-300' }}">
                                {{ $line->credit > 0 ? '৳' . number_format($line->credit, 2) : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 border-t-2 border-gray-200">
                    <tr>
                        <td colspan="3" class="table-td font-bold text-gray-700">Totals</td>
                        <td class="table-td text-right font-bold text-indigo-700">
                            ৳{{ number_format($txn->journalEntry->lines->sum('debit'), 2) }}
                        </td>
                        <td class="table-td text-right font-bold text-indigo-700">
                            ৳{{ number_format($txn->journalEntry->lines->sum('credit'), 2) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif

    {{-- Attachment --}}
    @if (!empty($txn->attachments))
        <div class="card p-4">
            <h3 class="font-semibold text-gray-900 text-sm mb-3">Attachments</h3>
            <div class="flex flex-wrap gap-2">
                @foreach ($txn->attachments as $path)
                    <a href="{{ Storage::url($path) }}" target="_blank"
                        class="flex items-center gap-2 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-xs text-indigo-600 hover:bg-gray-100">
                        📎 {{ basename($path) }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Reject Modal --}}
    @if ($showRejectModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Reject Transaction</h3>
                <p class="text-sm text-gray-500">No journal entry will be posted. Reason will be recorded in audit
                    trail.</p>
                <div>
                    <label class="label">Rejection Reason *</label>
                    <textarea wire:model="rejectionReason" rows="3" class="input"
                        placeholder="Explain why this transaction is being rejected…"></textarea>
                    @error('rejectionReason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button wire:click="reject" class="btn-danger flex-1"
                        wire:loading.attr="disabled">Reject</button>
                    <button wire:click="$set('showRejectModal', false)" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Reverse Modal --}}
    @if ($showReverseModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Reverse Transaction</h3>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm text-amber-800">
                    <strong>⚠ This will:</strong><br>
                    • Create a <strong>new reversal journal entry dated today</strong><br>
                    • Mark {{ $txn->transaction_number }} as Reversed<br>
                    • Create a new Reversal transaction document<br>
                    • This action cannot itself be undone (but can be re-reversed)
                </div>
                <div>
                    <label class="label">Reversal Reason * <span class="text-xs font-normal text-gray-400">(min 10
                            characters)</span></label>
                    <textarea wire:model="reversalReason" rows="3" class="input"
                        placeholder="e.g. Entered incorrect amount, wrong account selected — corrected by new transaction…"></textarea>
                    @error('reversalReason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button wire:click="reverse" class="btn-danger flex-1" wire:loading.attr="disabled"
                        wire:target="reverse">
                        <span wire:loading.remove wire:target="reverse">Post Reversal</span>
                        <span wire:loading wire:target="reverse">Processing…</span>
                    </button>
                    <button wire:click="$set('showReverseModal', false)" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
