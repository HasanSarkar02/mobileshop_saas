<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-900">Billing & Subscriptions</h2>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-4">
        @foreach ([['label' => 'Total Shops', 'val' => $stats->total_shops, 'color' => 'gray'], ['label' => 'Active', 'val' => $stats->active_subs, 'color' => 'green'], ['label' => 'Trial', 'val' => $stats->trial_subs, 'color' => 'blue'], ['label' => 'Past Due', 'val' => $stats->past_due, 'color' => 'red'], ['label' => 'Due This Week', 'val' => $stats->due_this_week, 'color' => 'amber'], ['label' => 'MRR (৳)', 'val' => number_format($stats->mrr, 0), 'color' => 'indigo']] as $stat)
            <div class="card p-4 border-0 bg-{{ $stat['color'] }}-50">
                <div class="text-xs font-semibold text-{{ $stat['color'] }}-500 uppercase mb-1">{{ $stat['label'] }}
                </div>
                <div class="text-2xl font-bold text-{{ $stat['color'] }}-700">{{ $stat['val'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 items-center">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search shop…"
            class="input max-w-xs text-sm">
        <div class="flex flex-wrap gap-1">
            @foreach (['' => 'All', 'trial' => 'Trial', 'active' => 'Active', 'past_due' => 'Past Due', 'suspended' => 'Suspended'] as $val => $label)
                <button wire:click="$set('statusFilter', '{{ $val }}')"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors
                        {{ $statusFilter === $val ? 'bg-indigo-600 text-white' : 'bg-white border border-gray-200 text-gray-600' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Subscription Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Shop</th>
                        <th class="table-th">Plan</th>
                        <th class="table-th">Billing</th>
                        <th class="table-th text-right">Amount</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Next Due</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($subscriptions as $sub)
                        @php
                            $overdue =
                                $sub->status === 'past_due' ||
                                ($sub->next_billing_date && $sub->next_billing_date < now()->toDateString());
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $overdue ? 'bg-red-50' : '' }}"
                            wire:key="sub-{{ $sub->id }}">
                            <td class="table-td">
                                <div class="font-semibold text-gray-900">{{ $sub->shop_name }}</div>
                                <div class="text-xs text-gray-400">{{ $sub->shop_phone }}</div>
                            </td>
                            <td class="table-td font-medium text-gray-700">{{ $sub->plan_name }}</td>
                            <td class="table-td text-xs text-gray-500 capitalize">{{ $sub->billing_cycle }}</td>
                            <td class="table-td text-right font-bold">৳{{ number_format($sub->price_at_signup, 0) }}
                            </td>
                            <td class="table-td">
                                @php$sc = match ($sub->status) {
                                        'active' => 'badge-green',
                                        'trial' => 'badge-blue',
                                        'past_due' => 'badge-red',
                                        'suspended' => 'badge-gray',
                                        default => 'badge-yellow',
                                }; @endphp
                                <span class="badge {{ $sc }} text-xs">{{ ucfirst($sub->status) }}</span>
                            </td>
                            <td class="table-td text-sm {{ $overdue ? 'text-red-600 font-bold' : 'text-gray-500' }}">
                                {{ $sub->next_billing_date
                                    ? \Carbon\Carbon::parse($sub->next_billing_date)->format('d M Y')
                                    : ($sub->trial_ends_at
                                        ? 'Trial: ' . \Carbon\Carbon::parse($sub->trial_ends_at)->format('d M')
                                        : '—') }}
                            </td>
                            <td class="table-td">
                                <div class="flex flex-wrap gap-2">
                                    @if (in_array($sub->status, ['trial']))
                                        <button wire:click="extendTrial({{ $sub->id }})"
                                            class="text-xs text-blue-600 hover:underline">+7 Days</button>
                                    @endif
                                    @if (in_array($sub->status, ['past_due', 'trial', 'suspended']))
                                        <button wire:click="markPaid({{ $sub->id }})"
                                            wire:confirm="Mark as paid and activate?"
                                            class="text-xs text-green-600 hover:underline">Mark Paid</button>
                                    @endif
                                    @if ($sub->status === 'active')
                                        <button wire:click="sendDueReminder({{ $sub->id }})"
                                            class="text-xs text-indigo-600 hover:underline">Send Reminder</button>
                                        <button wire:click="suspend({{ $sub->id }})"
                                            wire:confirm="Suspend this shop?"
                                            class="text-xs text-red-400 hover:underline">Suspend</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-td text-center text-gray-400 py-10">No subscriptions.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($subscriptions->hasPages())
            <div class="px-4 py-3 border-t">{{ $subscriptions->links() }}</div>
        @endif
    </div>
</div>
