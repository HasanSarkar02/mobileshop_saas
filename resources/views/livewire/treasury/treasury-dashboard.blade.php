<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Treasury & Cash Management</h2>
        <a href="{{ route('treasury.create') }}" wire:navigate class="btn-primary">
            + New Transaction
        </a>
    </div>

    {{-- Pending Approval Banner --}}
    @if ($this->pendingCount > 0)
        <div class="card p-4 bg-amber-50 border-amber-300 flex items-center gap-4">
            <div class="flex-1">
                <div class="font-semibold text-amber-900">
                    ⏳ {{ $this->pendingCount }} treasury transaction(s) awaiting your approval
                </div>
                <div class="text-xs text-amber-700 mt-0.5">
                    Equity, loan, and above-threshold transactions require Owner approval before the journal is posted.
                </div>
            </div>
            <button wire:click="$set('statusFilter', 'pending_approval')"
                class="btn-sm bg-amber-600 text-white hover:bg-amber-700 rounded-lg px-3 py-1.5 text-xs font-medium whitespace-nowrap">
                Review Now →
            </button>
        </div>
    @endif

    {{-- Cash Position Cards --}}
    <div class="card p-5">
        <h3 class="font-semibold text-gray-900 text-sm mb-4">Live Cash Position
            <span class="text-xs text-gray-400 font-normal ml-1">(real-time GL balances)</span>
        </h3>
        @php
            $accounts = $this->cashPosition;
            $total = $accounts->sum('balance');
        @endphp
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
            @forelse($accounts as $acc)
                <div class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">{{ $acc->name }}</div>
                        <div class="text-xs text-gray-400 capitalize">{{ $acc->provider ?? 'other' }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold {{ (float) $acc->balance < 0 ? 'text-red-600' : 'text-gray-900' }}">
                            ৳{{ number_format($acc->balance, 2) }}
                        </div>
                        @if ((float) $acc->balance < 0)
                            <div class="text-xs text-red-400">Negative</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center text-gray-400 text-sm py-4">
                    No payment accounts configured.
                    <a href="{{ route('settings') }}" wire:navigate class="text-indigo-600 hover:underline">Add accounts
                        →</a>
                </div>
            @endforelse
        </div>
        <div class="border-t border-gray-200 pt-3 flex justify-between items-center">
            <span class="text-sm font-semibold text-gray-600">Total Cash & Bank</span>
            <span class="text-xl font-bold {{ $total < 0 ? 'text-red-700' : 'text-indigo-700' }}">
                ৳{{ number_format($total, 2) }}
            </span>
        </div>
    </div>

    {{-- Quick Action Buttons --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-3">
        @foreach ([['label' => 'Transfer', 'type' => 'account_transfer', 'color' => 'bg-blue-50 text-blue-700 hover:bg-blue-100'], ['label' => 'Bank Deposit', 'type' => 'bank_deposit', 'color' => 'bg-green-50 text-green-700 hover:bg-green-100'], ['label' => 'Wallet Cashout', 'type' => 'wallet_cashout', 'color' => 'bg-pink-50 text-pink-700 hover:bg-pink-100'], ['label' => 'Owner Drawings', 'type' => 'owner_drawings', 'color' => 'bg-purple-50 text-purple-700 hover:bg-purple-100'], ['label' => 'Cash Adjustment', 'type' => 'cash_over', 'color' => 'bg-amber-50 text-amber-700 hover:bg-amber-100']] as $qa)
            <a href="{{ route('treasury.create') }}?type={{ $qa['type'] }}" wire:navigate
                class="{{ $qa['color'] }} rounded-xl p-3 text-center text-xs font-semibold transition-colors cursor-pointer block">
                {{ $qa['label'] }}
            </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <select wire:model.live="statusFilter" class="input text-sm w-auto">
            <option value="">All Statuses</option>
            @foreach (\App\Enums\TreasuryTransactionStatus::cases() as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="typeFilter" class="input text-sm w-auto">
            <option value="">All Categories</option>
            @foreach ($this->categories as $cat)
                <option value="{{ $cat->value }}">{{ $cat->label() }}</option>
            @endforeach
        </select>
        <input wire:model.live="dateFrom" type="date" class="input text-sm w-auto">
        <input wire:model.live="dateTo" type="date" class="input text-sm w-auto">
        @if ($statusFilter || $typeFilter || $dateFrom || $dateTo)
            <button
                wire:click="$set('statusFilter', ''); $set('typeFilter', ''); $set('dateFrom', ''); $set('dateTo', '')"
                class="text-xs text-gray-500 hover:text-gray-700">
                Clear ×
            </button>
        @endif
    </div>

    {{-- Transaction List --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Ref</th>
                        <th class="table-th">Date</th>
                        <th class="table-th">Type</th>
                        <th class="table-th">Description</th>
                        <th class="table-th">Flow</th>
                        <th class="table-th text-right">Amount</th>
                        <th class="table-th text-right">Fee</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">By</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $txn)
                        <tr class="hover:bg-gray-50 {{ in_array($txn->status->value, ['reversed', 'rejected']) ? 'opacity-60' : '' }}"
                            wire:key="txn-{{ $txn->id }}">
                            <td class="table-td">
                                <a href="{{ route('treasury.show', $txn) }}" wire:navigate
                                    class="font-mono font-semibold text-indigo-600 hover:underline text-xs">
                                    {{ $txn->transaction_number }}
                                </a>
                            </td>
                            <td class="table-td text-gray-500 text-xs whitespace-nowrap">
                                {{ $txn->transaction_date->format('d M Y') }}
                            </td>
                            <td class="table-td">
                                <div class="text-xs font-medium text-gray-700">
                                    {{ $txn->typeIcon() }} {{ $txn->transaction_type->label() }}
                                </div>
                                <span class="badge {{ $txn->transaction_category->badgeClass() }} text-xs mt-0.5">
                                    {{ $txn->transaction_category->label() }}
                                </span>
                            </td>
                            <td class="table-td text-gray-700 text-sm max-w-[180px] truncate">
                                {{ $txn->description }}
                                @if ($txn->reference_number)
                                    <div class="text-xs text-gray-400">Ref: {{ $txn->reference_number }}</div>
                                @endif
                            </td>
                            <td class="table-td text-xs text-gray-500">
                                {{ $txn->directionLabel() }}
                            </td>
                            <td class="table-td text-right">
                                <div class="font-bold text-gray-900">৳{{ number_format($txn->amount, 2) }}</div>
                                @if ($txn->net_amount != $txn->amount)
                                    <div class="text-xs text-gray-400">Net: ৳{{ number_format($txn->net_amount, 2) }}
                                    </div>
                                @endif
                            </td>
                            <td class="table-td text-right text-red-500 text-sm">
                                {{ $txn->fee_amount > 0 ? '৳' . number_format($txn->fee_amount, 2) : '—' }}
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $txn->status->badgeClass() }} text-xs">
                                    {{ $txn->status->label() }}
                                </span>
                            </td>
                            <td class="table-td text-xs text-gray-400">{{ $txn->createdBy?->name }}</td>
                            <td class="table-td">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('treasury.show', $txn) }}" wire:navigate
                                        class="text-xs text-indigo-600 hover:underline font-medium">View</a>

                                    @if ($txn->isPending() && auth()->user()->isOwner())
                                        <button wire:click="approve({{ $txn->id }})"
                                            wire:confirm="Approve {{ $txn->transaction_number }}?"
                                            class="text-xs text-green-600 hover:underline font-medium">
                                            Approve
                                        </button>
                                        <button wire:click="openRejectModal({{ $txn->id }})"
                                            class="text-xs text-red-500 hover:underline font-medium">
                                            Reject
                                        </button>
                                    @endif

                                    @if ($txn->isDraft())
                                        <a href="{{ route('treasury.edit', $txn) }}" wire:navigate
                                            class="text-xs text-gray-500 hover:underline font-medium">
                                            Edit
                                        </a>
                                        <button wire:click="deleteDraft({{ $txn->id }})"
                                            wire:confirm="Delete this draft?"
                                            class="text-xs text-red-400 hover:underline font-medium">
                                            Delete
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="table-td text-center text-gray-400 py-12">
                                No treasury transactions yet.
                                <a href="{{ route('treasury.create') }}" wire:navigate
                                    class="text-indigo-600 hover:underline">Create one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($transactions->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $transactions->links() }}</div>
        @endif
    </div>

    {{-- Reject Modal --}}
    @if ($showRejectModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Reject Transaction</h3>
                <p class="text-sm text-gray-500">No accounting entry will be made. Provide a clear reason.</p>
                <div>
                    <label class="label">Rejection Reason *</label>
                    <textarea wire:model="rejectionReason" rows="3" class="input"
                        placeholder="e.g. Amount incorrect, wrong account, needs more documentation…"></textarea>
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
</div>
