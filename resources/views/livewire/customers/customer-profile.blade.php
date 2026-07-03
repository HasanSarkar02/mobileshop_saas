<div class="max-w-5xl mx-auto space-y-5">

    {{-- ── Header Card ── --}}
    <div class="card p-5">
        <div class="flex flex-col sm:flex-row gap-5">
            {{-- Photo --}}
            <div class="shrink-0">
                @if ($customer->photo_path)
                    <img src="{{ Storage::url($customer->photo_path) }}"
                        class="w-20 h-20 rounded-2xl object-cover border-2 border-gray-200">
                @else
                    <div class="w-20 h-20 rounded-2xl bg-indigo-100 flex items-center justify-center">
                        <span
                            class="text-2xl font-bold text-indigo-600">{{ strtoupper(substr($customer->name, 0, 1)) }}</span>
                    </div>
                @endif
            </div>

            {{-- Info --}}
            <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-start gap-3 mb-2">
                    <h2 class="text-xl font-bold text-gray-900">{{ $customer->name }}</h2>
                    <span
                        class="badge {{ $customer->customer_type->badgeClass() }}">{{ $customer->customer_type->label() }}</span>
                    @if (!$customer->is_active)
                        <span class="badge badge-red">Inactive</span>
                    @endif
                </div>
                <div class="grid sm:grid-cols-2 gap-x-8 gap-y-1 text-sm">
                    <div class="flex gap-2"><span class="text-gray-400 w-20 shrink-0">Phone</span><span
                            class="font-medium">{{ $customer->phone }}</span></div>
                    @if ($customer->phone_alt)
                        <div class="flex gap-2"><span class="text-gray-400 w-20 shrink-0">Alt.
                                Phone</span><span>{{ $customer->phone_alt }}</span></div>
                    @endif
                    @if ($customer->email)
                        <div class="flex gap-2"><span
                                class="text-gray-400 w-20 shrink-0">Email</span><span>{{ $customer->email }}</span>
                        </div>
                    @endif
                    @if ($customer->address)
                        <div class="flex gap-2 sm:col-span-2"><span
                                class="text-gray-400 w-20 shrink-0">Address</span><span>{{ $customer->address }}{{ $customer->thana ? ', ' . $customer->thana : '' }}{{ $customer->district ? ', ' . $customer->district : '' }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="flex sm:flex-col gap-2 shrink-0">
                <a href="{{ route('customers.edit', $customer) }}" wire:navigate class="btn-secondary btn-sm">Edit</a>
                @if ($customer->current_balance > 0)
                    <button wire:click="$set('showPaymentForm', true)" class="btn-success btn-sm">
                        Collect Payment
                    </button>
                @endif
            </div>
            @if ($customer->current_balance > 0 && auth()->user()->isOwner())
                <button wire:click="sendDueReminder" wire:confirm="Send due reminder SMS to {{ $customer->name }}?"
                    class="btn-secondary btn-sm">
                    📱 Send Due Reminder
                </button>
            @endif
        </div>
    </div>

    {{-- ── Financial Summary Cards ── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        @php
            $financialCards = [
                [
                    'label' => 'Current Due',
                    'value' => '৳' . number_format($customer->current_balance, 2),
                    'color' => $customer->current_balance > 0 ? 'bg-red-50 text-red-700' : 'bg-green-50 text-green-700',
                ],
                [
                    'label' => 'Credit Limit',
                    'value' =>
                        $customer->credit_limit > 0 ? '৳' . number_format($customer->credit_limit, 2) : 'Unlimited',
                    'color' => 'bg-blue-50 text-blue-700',
                ],
                [
                    'label' => 'Total Purchases',
                    'value' => '৳' . number_format($customer->total_purchase_amount, 2),
                    'color' => 'bg-indigo-50 text-indigo-700',
                ],
                [
                    'label' => 'Total Paid',
                    'value' => '৳' . number_format($customer->total_paid_amount, 2),
                    'color' => 'bg-gray-50 text-gray-700',
                ],
            ];
        @endphp
        @foreach ($financialCards as $card)
            <div class="card p-4 border-0 {{ $card['color'] }}">
                <div class="text-lg font-bold">{{ $card['value'] }}</div>
                <div class="text-xs font-medium mt-0.5 opacity-75">{{ $card['label'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- ── Payment Collection Form ── --}}
    <div wire:show="showPaymentForm" class="card p-5 border-green-200 bg-green-50">
        <h3 class="font-semibold text-green-900 mb-4">Collect Payment</h3>
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="label">Amount (৳) *</label>
                <input wire:model="paymentAmount" type="number" step="0.01" min="1"
                    max="{{ $customer->current_balance }}" placeholder="0.00"
                    class="input @error('paymentAmount') input-error @enderror">
                <p class="text-xs text-green-600 mt-0.5">Max: ৳{{ number_format($customer->current_balance, 2) }}</p>
                @error('paymentAmount')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label">Received Via *</label>
                <select wire:model="paymentAccountId" class="input @error('paymentAccountId') input-error @enderror">
                    <option value="0">Select account…</option>
                    @foreach ($this->paymentAccounts as $pa)
                        <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                    @endforeach
                </select>
                @error('paymentAccountId')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label">Notes (optional)</label>
                <input wire:model="paymentNotes" type="text" class="input" placeholder="Reference, receipt no…">
            </div>
        </div>
        <div class="flex gap-2 mt-4">
            <button wire:click="recordPayment" class="btn-success btn-sm" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="recordPayment">Record Payment</span>
                <span wire:loading wire:target="recordPayment">Processing…</span>
            </button>
            <button wire:click="$set('showPaymentForm', false)" class="btn-secondary btn-sm">Cancel</button>
        </div>
    </div>

    {{-- ── Tabs ── --}}
    <div class="card overflow-hidden">
        <nav class="flex border-b border-gray-200 overflow-x-auto">
            @foreach ([['key' => 'overview', 'label' => 'Overview'], ['key' => 'transactions', 'label' => 'Transaction History'], ['key' => 'purchases', 'label' => 'Purchase History'], ['key' => 'documents', 'label' => 'Documents'], ['key' => 'guarantor', 'label' => 'Guarantor']] as $tab)
                <button wire:click="$set('activeTab', '{{ $tab['key'] }}')"
                    class="px-5 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors
                        {{ $activeTab === $tab['key'] ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>

        {{-- Overview Tab --}}
        <div wire:show="activeTab === 'overview'" class="p-5 space-y-4">
            {{-- Personal Details --}}
            <div class="grid sm:grid-cols-2 gap-4">
                @php
                    $details = array_filter(
                        [
                            ['label' => 'Date of Birth', 'value' => $customer->date_of_birth?->format('d M Y')],
                            ['label' => 'Gender', 'value' => $customer->gender ? ucfirst($customer->gender) : null],
                            ['label' => 'Occupation', 'value' => $customer->occupation],
                            [
                                'label' => 'ID Type',
                                'value' => $customer->id_type
                                    ? \App\Enums\CustomerIdType::from($customer->id_type)->label()
                                    : null,
                            ],
                            ['label' => 'ID Number', 'value' => $customer->id_number],
                            ['label' => 'Customer Since', 'value' => $customer->created_at->format('d M Y')],
                        ],
                        fn($d) => !empty($d['value']),
                    );
                @endphp
                @foreach ($details as $detail)
                    <div class="flex gap-3">
                        <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">{{ $detail['label'] }}</span>
                        <span class="text-sm text-gray-800">{{ $detail['value'] }}</span>
                    </div>
                @endforeach
            </div>
            @if ($customer->notes)
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800">
                    <strong class="font-medium">Notes:</strong> {{ $customer->notes }}
                </div>
            @endif

            {{-- Recent Transactions --}}
            <div>
                <h4 class="font-semibold text-gray-900 text-sm mb-3">Recent Activity</h4>
                <div class="space-y-2">
                    @forelse($this->recentTransactions as $tx)
                        <div class="flex items-center gap-3 text-sm py-2 border-b border-gray-50 last:border-0">
                            <span
                                class="{{ $tx->direction === 'debit' ? 'text-red-600' : 'text-green-600' }} font-bold w-4">
                                {{ $tx->direction === 'debit' ? '+' : '−' }}
                            </span>
                            <span class="flex-1 text-gray-700">{{ $tx->transaction_type->label() }}</span>
                            <span
                                class="font-semibold {{ $tx->direction === 'debit' ? 'text-red-600' : 'text-green-600' }}">
                                ৳{{ number_format($tx->amount, 2) }}
                            </span>
                            <span class="text-gray-400 text-xs">{{ $tx->created_at->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400">No transactions yet.</p>
                    @endforelse
                </div>
            </div>



            {{-- Write-off (owner only) --}}
            @if ($customer->current_balance > 0 && auth()->user()->isOwner())
                <div class="border-t border-gray-100 pt-4">
                    <div wire:show="!showWriteOffForm">
                        <button wire:click="$set('showWriteOffForm', true)"
                            class="text-xs text-red-500 hover:underline">
                            Write off bad debt…
                        </button>
                    </div>
                    <div wire:show="showWriteOffForm" class="bg-red-50 border border-red-200 rounded-xl p-4 space-y-3">
                        <p class="text-xs font-semibold text-red-700">⚠️ Write off — this creates a permanent Bad Debt
                            Expense journal entry.</p>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="label text-xs">Amount (৳) *</label>
                                <input wire:model="writeOffAmount" type="number" step="0.01" min="1"
                                    max="{{ $customer->current_balance }}" class="input text-sm">
                                @error('writeOffAmount')
                                    <p class="error">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="label text-xs">Reason *</label>
                                <input wire:model="writeOffNotes" type="text" class="input text-sm"
                                    placeholder="Reason for write-off">
                                @error('writeOffNotes')
                                    <p class="error">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button wire:click="confirmWriteOff"
                                wire:confirm="Permanently write off this amount? This cannot be undone."
                                class="btn-danger btn-sm">Write Off</button>
                            <button wire:click="$set('showWriteOffForm', false)"
                                class="btn-secondary btn-sm">Cancel</button>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Transactions Tab --}}
        <div wire:show="activeTab === 'transactions'">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Date</th>
                        <th class="table-th">Type</th>
                        <th class="table-th">Notes</th>
                        <th class="table-th text-right">Amount</th>
                        <th class="table-th text-right">Balance After</th>
                        <th class="table-th">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($transactions as $tx)
                        <tr>
                            <td class="table-td text-gray-500 text-xs">{{ $tx->created_at->format('d M Y H:i') }}</td>
                            <td class="table-td">
                                <span class="badge {{ $tx->direction === 'debit' ? 'badge-red' : 'badge-green' }}">
                                    {{ $tx->transaction_type->label() }}
                                </span>
                            </td>
                            <td class="table-td text-gray-500 text-xs">{{ $tx->notes ?? '—' }}</td>
                            <td
                                class="table-td text-right font-semibold {{ $tx->direction === 'debit' ? 'text-red-600' : 'text-green-600' }}">
                                {{ $tx->direction === 'debit' ? '+' : '−' }}৳{{ number_format($tx->amount, 2) }}
                            </td>
                            <td class="table-td text-right font-mono text-sm font-semibold text-gray-800">
                                ৳{{ number_format($tx->running_balance, 2) }}
                            </td>
                            <td class="table-td text-gray-400 text-xs">{{ $tx->createdBy?->name ?? 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-td text-center text-gray-400 py-8">No transactions yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($transactions->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $transactions->links() }}</div>
            @endif
        </div>

        {{-- Purchases Tab --}}
        <div wire:show="activeTab === 'purchases'">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Invoice</th>
                        <th class="table-th">Date</th>
                        <th class="table-th">Items</th>
                        <th class="table-th text-right">Total</th>
                        <th class="table-th">Payment</th>
                        <th class="table-th">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sales as $sale)
                        <tr
                            class="{{ $sale->status->value === 'voided' ? 'opacity-50 bg-red-50' : 'hover:bg-gray-50' }}">
                            <td class="table-td">
                                <a href="{{ route('sales.show', $sale) }}" wire:navigate
                                    class="font-mono font-semibold text-indigo-600 hover:underline text-sm">
                                    {{ $sale->sale_number }}
                                </a>
                            </td>
                            <td class="table-td text-gray-500 text-xs">
                                {{ $sale->confirmed_at?->format('d M Y') }}
                            </td>
                            <td class="table-td">
                                @foreach ($sale->items->take(2) as $item)
                                    <div class="text-xs text-gray-700">
                                        {{ $item->product_name }}
                                        @if ($item->serial_number)
                                            <span class="font-mono text-indigo-400">{{ $item->serial_number }}</span>
                                        @endif
                                    </div>
                                @endforeach
                                @if ($sale->items->count() > 2)
                                    <div class="text-xs text-gray-400">+{{ $sale->items->count() - 2 }} more</div>
                                @endif
                            </td>
                            <td
                                class="table-td text-right font-bold {{ $sale->status->value === 'voided' ? 'line-through text-gray-400' : 'text-gray-900' }}">
                                ৳{{ number_format($sale->grand_total, 2) }}
                            </td>
                            <td class="table-td text-xs text-gray-500">
                                {{ $sale->paymentSummary() }}
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $sale->status->badgeClass() }}">
                                    {{ $sale->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="table-td text-center text-gray-400 py-8">
                                No purchases yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($sales->hasPages())
                <div class="px-4 py-3 border-t border-gray-100">{{ $sales->links() }}</div>
            @endif
        </div>

        {{-- Documents Tab --}}
        <div wire:show="activeTab === 'documents'" class="p-5">
            <div class="grid sm:grid-cols-3 gap-5">
                @php
                    $docs = [
                        ['label' => 'Customer Photo', 'path' => $customer->photo_path],
                        [
                            'label' =>
                                'ID Front (' .
                                (\App\Enums\CustomerIdType::tryFrom($customer->id_type ?? '')?->label() ?? 'ID') .
                                ')',
                            'path' => $customer->id_front_path,
                        ],
                        ['label' => 'ID Back', 'path' => $customer->id_back_path],
                    ];
                @endphp
                @foreach ($docs as $doc)
                    <div class="space-y-2">
                        <p class="text-xs font-semibold text-gray-500">{{ $doc['label'] }}</p>
                        @if ($doc['path'])
                            <a href="{{ Storage::url($doc['path']) }}" target="_blank" class="block">
                                <img src="{{ Storage::url($doc['path']) }}"
                                    class="w-full h-40 object-cover rounded-xl border border-gray-200 hover:opacity-80 transition-opacity">
                            </a>
                        @else
                            <div
                                class="w-full h-40 rounded-xl border-2 border-dashed border-gray-200 flex items-center justify-center text-gray-300 text-xs">
                                Not uploaded
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            @if ($customer->id_number)
                <div class="mt-4 text-sm">
                    <span class="text-gray-500">ID Number:</span>
                    <span class="font-mono font-semibold text-gray-800 ml-2">{{ $customer->id_number }}</span>
                </div>
            @endif
        </div>

        {{-- Guarantor Tab --}}
        <div wire:show="activeTab === 'guarantor'" class="p-5">
            @if ($g = $customer->guarantor)
                <div class="space-y-5">
                    <div class="flex items-start gap-5">
                        @if ($g->photo_path)
                            <img src="{{ Storage::url($g->photo_path) }}"
                                class="w-20 h-20 rounded-xl object-cover border-2 border-gray-200 shrink-0">
                        @endif
                        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-2 text-sm flex-1">
                            @foreach ([['label' => 'Name', 'value' => $g->name], ['label' => 'Phone', 'value' => $g->phone], ['label' => 'Alt Phone', 'value' => $g->phone_alt], ['label' => 'Relation', 'value' => $g->relation->label()], ['label' => 'Address', 'value' => $g->address], ['label' => 'NID Number', 'value' => $g->nid_number]] as $row)
                                @if ($row['value'])
                                    <div class="flex gap-3">
                                        <span class="text-gray-400 w-24 shrink-0">{{ $row['label'] }}</span>
                                        <span class="font-medium text-gray-800">{{ $row['value'] }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    {{-- Guarantor NID docs --}}
                    <div class="grid sm:grid-cols-2 gap-4">
                        @foreach ([['label' => 'NID Front', 'path' => $g->nid_front_path], ['label' => 'NID Back', 'path' => $g->nid_back_path]] as $doc)
                            @if ($doc['path'])
                                <div>
                                    <p class="text-xs font-semibold text-gray-500 mb-2">{{ $doc['label'] }}</p>
                                    <a href="{{ Storage::url($doc['path']) }}" target="_blank">
                                        <img src="{{ Storage::url($doc['path']) }}"
                                            class="w-full h-36 object-cover rounded-xl border border-gray-200 hover:opacity-80 transition-opacity">
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-12 text-gray-400">
                    <p class="text-sm">No guarantor information added.</p>
                    <a href="{{ route('customers.edit', $customer) }}" wire:navigate
                        class="text-indigo-600 hover:underline text-sm mt-1 inline-block">Add guarantor</a>
                </div>
            @endif
        </div>
    </div>
</div>
