<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Billing & Subscriptions</h2>
        <div class="flex gap-2">
            <a href="{{ route('admin.plans') }}" wire:navigate class="btn-secondary btn-sm">
                ⚙ Manage Plans
            </a>
            <button wire:click="openAssignModal()" class="btn-primary btn-sm">
                + Assign Plan
            </button>
        </div>
    </div>

    {{-- Stats --}}
    @php $s = $this->stats; @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 xl:grid-cols-8 gap-3">
        @foreach ([['label' => 'Total Shops', 'val' => $s->total_shops, 'color' => 'gray'], ['label' => 'Active', 'val' => $s->active, 'color' => 'green'], ['label' => 'Trial', 'val' => $s->trial, 'color' => 'blue'], ['label' => 'Past Due', 'val' => $s->past_due, 'color' => 'red'], ['label' => 'Suspended', 'val' => $s->suspended, 'color' => 'gray'], ['label' => 'No Plan', 'val' => $s->no_sub, 'color' => 'amber'], ['label' => 'Due This Week', 'val' => $s->due_this_week, 'color' => 'orange'], ['label' => 'MRR (৳)', 'val' => number_format($s->mrr, 0), 'color' => 'indigo']] as $stat)
            <div class="card p-3 border-0 bg-{{ $stat['color'] }}-50 border border-{{ $stat['color'] }}-100">
                <div class="text-xs font-semibold text-{{ $stat['color'] }}-500 uppercase tracking-wider mb-1 truncate">
                    {{ $stat['label'] }}
                </div>
                <div class="text-xl font-bold text-{{ $stat['color'] }}-700">{{ $stat['val'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Shops without plan alert --}}
    @if ($s->no_sub > 0)
        <div class="card p-4 bg-amber-50 border-amber-300 flex items-center gap-4">
            <div class="flex-1 text-amber-800 text-sm font-medium">
                ⚠ {{ $s->no_sub }} shop(s) have no subscription plan assigned.
            </div>
            <button wire:click="openAssignModal()"
                class="btn-sm bg-amber-600 text-white rounded-lg px-3 py-1.5 text-xs font-medium">
                Assign Plan →
            </button>
        </div>
    @endif

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search shop name or email…"
            class="input max-w-xs text-sm">
        <select wire:model.live="planFilter" class="input text-sm w-auto">
            <option value="">All Plans</option>
            @foreach ($this->plans as $plan)
                <option value="{{ $plan->id }}">{{ $plan->name }}</option>
            @endforeach
        </select>
        <div class="flex flex-wrap gap-1">
            @foreach (['' => 'All', 'trial' => 'Trial', 'active' => 'Active', 'past_due' => 'Past Due', 'suspended' => 'Suspended'] as $val => $label)
                <button wire:click="$set('statusFilter', '{{ $val }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                        {{ $statusFilter === $val ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-indigo-300' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Shop</th>
                        <th class="table-th">Plan</th>
                        <th class="table-th">Cycle</th>
                        <th class="table-th text-right">Amount (৳)</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Next Due</th>
                        <th class="table-th">Started</th>
                        <th class="table-th min-w-[220px]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($this->subscriptions as $sub)
                        @php
                            $daysUntil = $sub->daysUntilDue();
                            $isOverdue =
                                $sub->next_billing_date &&
                                $sub->next_billing_date->isPast() &&
                                !in_array($sub->status, ['cancelled', 'suspended']);
                            $isDueSoon = $daysUntil !== null && $daysUntil <= 7 && $daysUntil >= 0;
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $isOverdue ? 'bg-red-50/50' : '' }}"
                            wire:key="sub-{{ $sub->id }}">
                            <td class="table-td">
                                <div class="font-semibold text-gray-900">{{ $sub->shop?->name }}</div>
                                <div class="text-xs text-gray-400">{{ $sub->shop?->phone }}</div>
                                <div class="text-xs text-gray-400">{{ $sub->shop?->email }}</div>
                            </td>
                            <td class="table-td">
                                <span class="font-medium text-gray-700">{{ $sub->plan?->name }}</span>
                            </td>
                            <td class="table-td text-xs text-gray-500 capitalize">{{ $sub->billing_cycle }}</td>
                            <td class="table-td text-right font-bold text-gray-900">
                                ৳{{ number_format($sub->price_at_signup, 0) }}
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $sub->statusBadgeClass() }} text-xs">
                                    {{ ucfirst(str_replace('_', ' ', $sub->status)) }}
                                </span>
                                @if ($sub->status === 'trial' && $sub->trial_ends_at)
                                    <div class="text-xs text-blue-500 mt-0.5">
                                        Trial ends: {{ $sub->trial_ends_at->format('d M') }}
                                    </div>
                                @endif
                            </td>
                            <td class="table-td">
                                @if ($sub->next_billing_date)
                                    <div
                                        class="text-sm {{ $isOverdue ? 'text-red-600 font-bold' : ($isDueSoon ? 'text-amber-600 font-semibold' : 'text-gray-600') }}">
                                        {{ $sub->next_billing_date->format('d M Y') }}
                                    </div>
                                    @if ($isOverdue)
                                        <div class="text-xs text-red-400">Overdue</div>
                                    @elseif($isDueSoon)
                                        <div class="text-xs text-amber-400">Due in {{ $daysUntil }}d</div>
                                    @endif
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="table-td text-xs text-gray-400">
                                {{ $sub->created_at->format('d M Y') }}
                            </td>
                            <td class="table-td">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    {{-- View Shop --}}
                                    <a href="{{ route('admin.shops.show', $sub->shop_id) }}" wire:navigate
                                        class="text-xs text-indigo-600 hover:underline font-medium">View</a>

                                    {{-- Trial extension --}}
                                    @if (in_array($sub->status, ['trial']))
                                        <button wire:click="extendTrial({{ $sub->id }})"
                                            class="text-xs text-blue-500 hover:underline font-medium">
                                            +7d Trial
                                        </button>
                                    @endif

                                    {{-- Mark Paid --}}
                                    @if (in_array($sub->status, ['past_due', 'trial', 'suspended']))
                                        <button wire:click="markPaid({{ $sub->id }})"
                                            wire:confirm="Mark as paid and activate subscription?"
                                            class="text-xs text-green-600 hover:underline font-medium">
                                            ✓ Paid
                                        </button>
                                    @endif

                                    {{-- Remind --}}
                                    @if (in_array($sub->status, ['active', 'past_due', 'trial']))
                                        <button wire:click="sendDueReminder({{ $sub->id }})"
                                            class="text-xs text-amber-500 hover:underline font-medium">
                                            📧 Remind
                                        </button>
                                    @endif

                                    {{-- Invoice --}}
                                    <button wire:click="openInvoiceModal({{ $sub->id }})"
                                        class="text-xs text-gray-500 hover:underline font-medium">
                                        + Invoice
                                    </button>

                                    {{-- Suspend / Reactivate --}}
                                    @if ($sub->status === 'active')
                                        <button wire:click="suspend({{ $sub->id }})"
                                            wire:confirm="Suspend this shop? They will lose access."
                                            class="text-xs text-red-400 hover:underline font-medium">
                                            Suspend
                                        </button>
                                    @elseif($sub->status === 'suspended')
                                        <button wire:click="reactivate({{ $sub->id }})"
                                            class="text-xs text-green-500 hover:underline font-medium">
                                            Reactivate
                                        </button>
                                    @endif

                                    {{-- Assign new plan --}}
                                    <button wire:click="openAssignModal({{ $sub->shop_id }})"
                                        class="text-xs text-indigo-400 hover:underline font-medium">
                                        Change Plan
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="table-td text-center text-gray-400 py-10">
                                No subscriptions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($this->subscriptions->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $this->subscriptions->links() }}</div>
        @endif
    </div>

    {{-- ── Assign Plan Modal ── --}}
    @if ($showAssignModal)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Assign Subscription Plan</h3>

                <div class="space-y-3">
                    @if (!$assignShopId)
                        <div>
                            <label class="label text-xs">Shop *</label>
                            <select wire:model="assignShopId" class="input text-sm">
                                <option value="">Select shop…</option>
                                @foreach ($this->shopsWithoutSub as $shop)
                                    <option value="{{ $shop->id }}">{{ $shop->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="label text-xs">Plan *</label>
                        <select wire:model="assignPlanId" class="input text-sm">
                            <option value="0">Select plan…</option>
                            @foreach ($this->plans as $plan)
                                <option value="{{ $plan->id }}">
                                    {{ $plan->name }} —
                                    ৳{{ number_format($plan->monthly_price, 0) }}/mo
                                    @if ($plan->yearly_price)
                                        | ৳{{ number_format($plan->yearly_price, 0) }}/yr
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="label text-xs">Billing Cycle</label>
                            <select wire:model="assignCycle" class="input text-sm">
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>
                        <div>
                            <label class="label text-xs">Trial Days (0 = no trial)</label>
                            <input wire:model="assignTrialDays" type="number" min="0" max="90"
                                class="input text-sm" placeholder="14">
                        </div>
                    </div>

                    <div>
                        <label class="label text-xs">Notes</label>
                        <input wire:model="assignNotes" type="text" class="input text-sm"
                            placeholder="Optional admin notes…">
                    </div>

                    @if ($assignPlanId)
                        @php $selectedPlan = $this->plans->find($assignPlanId); @endphp
                        @if ($selectedPlan)
                            <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 text-sm">
                                <div class="font-semibold text-indigo-800">{{ $selectedPlan->name }} Plan</div>
                                <div class="text-xs text-indigo-600 mt-1 space-y-0.5">
                                    <div>Max Branches: {{ $selectedPlan->max_branches }}</div>
                                    <div>Max Employees: {{ $selectedPlan->max_employees }}</div>
                                    <div>Max Products: {{ $selectedPlan->max_products ?: 'Unlimited' }}</div>
                                    <div class="font-semibold mt-1">
                                        {{ $assignCycle === 'yearly' ? '৳' . number_format($selectedPlan->yearly_price ?? $selectedPlan->monthly_price * 12, 0) . '/year' : '৳' . number_format($selectedPlan->monthly_price, 0) . '/month' }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="flex gap-3">
                    <button wire:click="assignPlan" class="btn-primary flex-1" wire:loading.attr="disabled"
                        wire:target="assignPlan">
                        <span wire:loading.remove wire:target="assignPlan">Assign Plan</span>
                        <span wire:loading wire:target="assignPlan">Assigning…</span>
                    </button>
                    <button wire:click="$set('showAssignModal', false)" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Create Invoice Modal ── --}}
    @if ($showInvoiceModal)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Create Invoice</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="label text-xs">Amount (৳) *</label>
                        <input wire:model="invoiceAmount" type="number" min="1" step="0.01"
                            class="input text-sm @error('invoiceAmount') input-error @enderror">
                        @error('invoiceAmount')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">Due Date *</label>
                        <input wire:model="invoiceDueDate" type="date"
                            class="input text-sm @error('invoiceDueDate') input-error @enderror">
                        @error('invoiceDueDate')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div>
                    <label class="label text-xs">Notes</label>
                    <input wire:model="invoiceNotes" type="text" class="input text-sm" placeholder="Optional…">
                </div>
                <div class="flex gap-3">
                    <button wire:click="createInvoice" class="btn-primary flex-1" wire:loading.attr="disabled">
                        <span wire:loading.remove>Create Invoice</span>
                        <span wire:loading>Creating…</span>
                    </button>
                    <button wire:click="$set('showInvoiceModal', false)" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
