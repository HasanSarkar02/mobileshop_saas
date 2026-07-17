<div class="max-w-3xl mx-auto space-y-5">

    <h2 class="text-xl font-bold text-gray-900">My Subscription</h2>

    @php
        $sub = $this->subscription;
        $stats = $this->shopStats;
    @endphp

    @if (!$sub)
        <div class="card p-8 text-center space-y-3">
            <div class="text-5xl">📋</div>
            <h3 class="font-bold text-gray-900">No Active Subscription</h3>
            <p class="text-sm text-gray-500">
                Your shop does not have an active subscription plan assigned yet.
                Please contact ShopERP support to activate your account.
            </p>
            <div class="text-sm text-gray-400 bg-gray-50 rounded-xl p-4 mt-2">
                <strong>Support:</strong>
                <a href="mailto:support@shoperp.com" class="text-indigo-600 hover:underline ml-1">
                    support@shoperp.com
                </a>
            </div>
        </div>
    @else
        {{-- Current Plan Banner --}}
        <div class="card p-5 bg-gradient-to-r from-indigo-700 to-indigo-900 text-white overflow-hidden relative">
            <div class="absolute right-0 top-0 w-32 h-32 bg-white/5 rounded-full -mr-8 -mt-8"></div>
            <div class="absolute right-8 bottom-0 w-20 h-20 bg-white/5 rounded-full mb-4"></div>
            <div class="relative z-10">
                <div class="text-indigo-200 text-xs font-semibold uppercase tracking-wider mb-1">Current Plan</div>
                <div class="text-2xl font-bold mb-1">{{ $sub->plan?->name ?? 'Custom Plan' }}</div>
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <span class="bg-white/20 px-2 py-0.5 rounded-full">
                        {{ ucfirst($sub->billing_cycle) }}
                        · ৳{{ number_format($sub->price_at_signup, 0) }}
                    </span>
                    <span class="bg-white/20 px-2 py-0.5 rounded-full">
                        {{ ucfirst(str_replace('_', ' ', $sub->status)) }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Status Card --}}
        <div class="grid sm:grid-cols-2 gap-5">
            <div class="card p-5 space-y-3">
                <h3 class="font-semibold text-gray-900 text-sm">Subscription Details</h3>
                @foreach ([['label' => 'Status', 'value' => ucfirst(str_replace('_', ' ', $sub->status))], ['label' => 'Plan', 'value' => $sub->plan?->name ?? '—'], ['label' => 'Billing', 'value' => ucfirst($sub->billing_cycle)], ['label' => 'Amount', 'value' => '৳' . number_format($sub->price_at_signup, 2)], ['label' => 'Period Start', 'value' => $sub->current_period_start?->format('d M Y')], ['label' => 'Period End', 'value' => $sub->current_period_end?->format('d M Y')], ['label' => 'Next Due', 'value' => $sub->next_billing_date?->format('d M Y') ?? '—']] as $row)
                    <div class="flex gap-3 text-sm">
                        <span class="text-gray-400 w-28 shrink-0">{{ $row['label'] }}</span>
                        <span class="text-gray-800 font-medium">{{ $row['value'] }}</span>
                    </div>
                @endforeach

                @if ($sub->status === 'trial' && $sub->trial_ends_at)
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-sm text-blue-800 mt-2">
                        ℹ Your trial ends on <strong>{{ $sub->trial_ends_at->format('d M Y') }}</strong>
                        ({{ $sub->trial_ends_at->diffForHumans() }}).
                        After trial, payment is required to continue.
                    </div>
                @endif

                @if ($sub->status === 'past_due')
                    <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-800 mt-2">
                        ⚠ Your payment is overdue. Please contact support to continue using ShopERP.
                    </div>
                @endif
            </div>

            {{-- Plan Limits --}}
            @if ($sub->plan)
                <div class="card p-5 space-y-4">
                    <h3 class="font-semibold text-gray-900 text-sm">Plan Limits & Usage</h3>
                    @foreach ([['label' => 'Branches', 'used' => $stats->branches, 'max' => $sub->plan->max_branches], ['label' => 'Employees', 'used' => $stats->employees, 'max' => $sub->plan->max_employees], ['label' => 'Products', 'used' => $stats->products, 'max' => $sub->plan->max_products]] as $limit)
                        @php
                            $pct = $limit['max'] > 0 ? min(100, round(($limit['used'] / $limit['max']) * 100)) : 0;
                            $color = $pct >= 90 ? 'red' : ($pct >= 70 ? 'amber' : 'indigo');
                        @endphp
                        <div class="space-y-1">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ $limit['label'] }}</span>
                                <span class="font-semibold text-gray-900">
                                    {{ $limit['used'] }} /
                                    {{ $limit['max'] ?: '∞' }}
                                </span>
                            </div>
                            @if ($limit['max'] > 0)
                                <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                    <div class="h-2 bg-{{ $color }}-500 rounded-full transition-all"
                                        style="width: {{ $pct }}%"></div>
                                </div>
                                @if ($pct >= 90)
                                    <p class="text-xs text-red-500">
                                        Approaching limit — contact support to upgrade.
                                    </p>
                                @endif
                            @else
                                <div class="h-2 bg-green-100 rounded-full overflow-hidden">
                                    <div class="h-2 bg-green-400 rounded-full" style="width:30%"></div>
                                </div>
                                <p class="text-xs text-green-500">Unlimited</p>
                            @endif
                        </div>
                    @endforeach

                    {{-- Features --}}
                    @if ($sub->plan->features)
                        <div class="border-t border-gray-100 pt-3">
                            <div class="text-xs text-gray-500 mb-2">Included Features</div>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($sub->plan->features as $feat)
                                    <span
                                        class="text-xs bg-green-50 text-green-700 border border-green-100 px-2 py-0.5 rounded-full">
                                        ✓ {{ ucwords(str_replace('_', ' ', $feat)) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- Invoice History --}}
    @if ($this->invoices->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm">Invoice History</h3>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Invoice</th>
                        <th class="table-th">Due Date</th>
                        <th class="table-th text-right">Amount</th>
                        <th class="table-th">Status</th>
                        <th class="table-th">Paid On</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($this->invoices as $inv)
                        @php $overdue = $inv->status === 'pending' && $inv->due_date->isPast(); @endphp
                        <tr class="hover:bg-gray-50 {{ $overdue ? 'bg-red-50/40' : '' }}">
                            <td class="table-td font-mono text-sm text-indigo-600">{{ $inv->invoice_number }}</td>
                            <td
                                class="table-td text-sm {{ $overdue ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                {{ $inv->due_date->format('d M Y') }}
                            </td>
                            <td class="table-td text-right font-bold">৳{{ number_format($inv->amount, 0) }}</td>
                            <td class="table-td">
                                @php
                                    $bc = match ($inv->status) {
                                        'paid' => 'badge-green',
                                        'pending' => $overdue ? 'badge-red' : 'badge-yellow',
                                        'waived' => 'badge-gray',
                                        default => 'badge-gray',
                                    };
                                @endphp
                                <span class="badge {{ $bc }} text-xs">
                                    {{ ucfirst($inv->status) }}
                                    @if ($overdue)
                                        (Overdue)
                                    @endif
                                </span>
                            </td>
                            <td class="table-td text-sm text-green-600">
                                {{ $inv->paid_at?->format('d M Y') ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Support Contact --}}
    <div class="card p-4 bg-gray-50 border-gray-200 text-sm text-gray-600">
        <strong>Need help?</strong> Contact ShopERP support:
        <a href="mailto:support@shoperp.com" class="text-indigo-600 hover:underline ml-1">support@shoperp.com</a>
        &nbsp;·&nbsp;
        To upgrade or change your plan, reach out to your account manager.
    </div>
</div>
