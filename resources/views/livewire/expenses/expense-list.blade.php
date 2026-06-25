<div class="space-y-4">

    {{-- Pending Approval Banner --}}
    @if ($this->pendingCount > 0)
        <div class="card p-4 bg-amber-50 border-amber-300 flex items-center gap-4">
            <div class="w-10 h-10 bg-amber-100 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="flex-1">
                <div class="font-semibold text-amber-900">
                    {{ $this->pendingCount }} expense(s) awaiting your approval
                </div>
                <div class="text-xs text-amber-700 mt-0.5">
                    These are above your approval threshold and need your review.
                </div>
            </div>
            <button wire:click="$set('status', 'pending')"
                class="btn-sm bg-amber-600 text-white hover:bg-amber-700 rounded-lg px-3 py-1.5 text-xs font-medium">
                Review Now →
            </button>
        </div>
    @endif

    {{-- Month Summary --}}
    @php $s = $this->monthSummary; @endphp
    <div class="card p-5 grid sm:grid-cols-2 gap-5">
        <div>
            <div class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">
                This Month's Approved Expenses
            </div>
            <div class="text-3xl font-bold text-red-600">৳{{ number_format($s['total'], 2) }}</div>
        </div>
        @if ($s['by_category']->isNotEmpty())
            <div>
                <div class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-2">Top Categories</div>
                <div class="space-y-1.5">
                    @foreach ($s['by_category'] as $name => $total)
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-red-400 h-full rounded-full"
                                    style="width: {{ $s['total'] > 0 ? round(($total / $s['total']) * 100) : 0 }}%">
                                </div>
                            </div>
                            <span class="text-xs text-gray-600 w-28 truncate">{{ $name }}</span>
                            <span
                                class="text-xs font-semibold text-gray-900 w-20 text-right">৳{{ number_format($total, 0) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 flex-wrap">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Description or ref…"
            class="input max-w-xs">
        <select wire:model.live="category" class="input w-auto">
            <option value="0">All categories</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}">
                    {{ $cat->parent_id ? '  › ' : '' }}{{ $cat->name }}
                </option>
            @endforeach
        </select>
        <select wire:model.live="status" class="input w-auto">
            <option value="">Active (non-voided)</option>
            @foreach (\App\Enums\ExpenseStatus::cases() as $s)
                <option value="{{ $s->value }}">{{ $s->label() }}</option>
            @endforeach
        </select>
        <input wire:model.live="dateFrom" type="date" class="input w-auto">
        <input wire:model.live="dateTo" type="date" class="input w-auto">
        <a href="{{ route('expenses.create') }}" wire:navigate class="btn-primary sm:ml-auto whitespace-nowrap">
            + Add Expense
        </a>
    </div>

    {{-- Expense Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Date</th>
                        <th class="table-th">Category</th>
                        <th class="table-th">Description</th>
                        <th class="table-th">Branch</th>
                        <th class="table-th">Payment</th>
                        <th class="table-th text-right">Amount</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">By</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($expenses as $exp)
                        <tr
                            class="hover:bg-gray-50 {{ $exp->status === \App\Enums\ExpenseStatus::Voided ? 'opacity-50' : '' }}">
                            <td class="table-td text-gray-500 text-sm">{{ $exp->expense_date->format('d M Y') }}</td>
                            <td class="table-td">
                                @if ($exp->category?->parent)
                                    <div class="text-xs text-gray-400">{{ $exp->category->parent->name }}</div>
                                @endif
                                <div class="font-medium text-sm text-gray-900">{{ $exp->category?->name }}</div>
                            </td>
                            <td class="table-td">
                                <div
                                    class="text-gray-700 text-sm {{ $exp->status === \App\Enums\ExpenseStatus::Voided ? 'line-through' : '' }}">
                                    {{ $exp->description }}
                                </div>
                                @if ($exp->reference_number)
                                    <div class="text-xs text-gray-400">Ref: {{ $exp->reference_number }}</div>
                                @endif
                                @if ($exp->void_reason)
                                    <div class="text-xs text-red-400">Void: {{ $exp->void_reason }}</div>
                                @endif
                                @if ($exp->rejection_reason)
                                    <div class="text-xs text-red-400">Rejected: {{ $exp->rejection_reason }}</div>
                                @endif
                            </td>
                            <td class="table-td text-gray-500 text-xs">{{ $exp->branch?->name }}</td>
                            <td class="table-td text-xs text-gray-500">{{ $exp->paymentAccount?->name }}</td>
                            <td class="table-td text-right font-bold text-red-600 text-base">
                                ৳{{ number_format($exp->amount, 2) }}
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $exp->status->badgeClass() }}">
                                    {{ $exp->status->label() }}
                                </span>
                            </td>
                            <td class="table-td text-gray-400 text-xs">{{ $exp->createdBy?->name }}</td>
                            <td class="table-td">
                                <div class="flex items-center gap-2 flex-wrap">
                                    {{-- Approve/Reject for pending --}}
                                    @if ($exp->status === \App\Enums\ExpenseStatus::Pending && auth()->user()->isOwner())
                                        <button wire:click="approve({{ $exp->id }})"
                                            class="text-xs text-green-600 hover:underline font-medium">
                                            Approve
                                        </button>
                                        <button wire:click="openRejectModal({{ $exp->id }})"
                                            class="text-xs text-red-500 hover:underline font-medium">
                                            Reject
                                        </button>
                                    @endif

                                    {{-- Void for approved --}}
                                    @if (
                                        $exp->status === \App\Enums\ExpenseStatus::Approved &&
                                            (auth()->user()->isOwner() || auth()->user()->can('accounting.manage_entries')))
                                        <button wire:click="openVoidModal({{ $exp->id }})"
                                            class="text-xs text-amber-600 hover:underline font-medium">
                                            Void
                                        </button>
                                    @endif

                                    {{-- Receipt --}}
                                    @if ($exp->receipt_path)
                                        <a href="{{ $exp->receiptUrl() }}" target="_blank"
                                            class="text-xs text-indigo-600 hover:underline">Receipt</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="table-td text-center text-gray-400 py-12">
                                No expenses found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($expenses->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $expenses->links() }}</div>
        @endif
    </div>

    {{-- Void Modal --}}
    @if ($showVoidModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Void Expense</h3>
                <p class="text-sm text-gray-500">
                    A reversal journal entry will be created. This cannot be undone.
                </p>
                <div>
                    <label class="label">Reason *</label>
                    <textarea wire:model="voidReason" rows="3" class="input" placeholder="e.g. Entered by mistake, duplicate entry…"></textarea>
                    @error('voidReason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button wire:click="void" wire:loading.attr="disabled" class="btn-danger flex-1">
                        <span wire:loading.remove>Void Expense</span>
                        <span wire:loading>Processing…</span>
                    </button>
                    <button wire:click="$set('showVoidModal', false)" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Reject Modal --}}
    @if ($showRejectModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Reject Expense</h3>
                <p class="text-sm text-gray-500">
                    Provide a reason. No accounting entry will be created.
                </p>
                <div>
                    <label class="label">Rejection Reason *</label>
                    <textarea wire:model="rejectionReason" rows="3" class="input"
                        placeholder="e.g. Duplicate, not authorized, incorrect amount…"></textarea>
                    @error('rejectionReason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button wire:click="reject" wire:loading.attr="disabled" class="btn-danger flex-1">
                        Reject Expense
                    </button>
                    <button wire:click="$set('showRejectModal', false)" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
