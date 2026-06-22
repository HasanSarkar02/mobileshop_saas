<div class="space-y-4">
    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="card p-4 border-0 bg-indigo-50">
            <div class="text-2xl font-bold text-indigo-700">{{ number_format($this->stats['total']) }}</div>
            <div class="text-xs text-indigo-500 font-medium mt-0.5">Total Customers</div>
        </div>
        <div class="card p-4 border-0 bg-amber-50">
            <div class="text-2xl font-bold text-amber-700">{{ number_format($this->stats['with_due']) }}</div>
            <div class="text-xs text-amber-500 font-medium mt-0.5">With Due Balance</div>
        </div>
        <div class="card p-4 border-0 bg-red-50">
            <div class="text-2xl font-bold text-red-700">৳{{ number_format($this->stats['total_due'], 0) }}</div>
            <div class="text-xs text-red-500 font-medium mt-0.5">Total Outstanding</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Name, phone, email…"
            class="input max-w-xs">
        <select wire:model.live="type" class="input w-auto">
            <option value="">All types</option>
            @foreach (\App\Enums\CustomerType::cases() as $ct)
                @if ($ct !== \App\Enums\CustomerType::WalkIn)
                    <option value="{{ $ct->value }}">{{ $ct->label() }}</option>
                @endif
            @endforeach
        </select>
        <select wire:model.live="balance" class="input w-auto">
            <option value="">All balances</option>
            <option value="with_due">Has due / baki</option>
            <option value="clear">Cleared</option>
        </select>
        <a href="{{ route('customers.create') }}" wire:navigate class="btn-primary sm:ml-auto whitespace-nowrap">
            + New Customer
        </a>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="table-th">Customer</th>
                        <th class="table-th">Phone</th>
                        <th class="table-th">Type</th>
                        <th class="table-th">Due Balance</th>
                        <th class="table-th">Total Purchases</th>
                        <th class="table-th">Guarantor</th>
                        <th class="table-th">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50">
                            <td class="table-td">
                                <div class="flex items-center gap-3">
                                    @if ($customer->photo_path)
                                        <img src="{{ Storage::url($customer->photo_path) }}"
                                            class="w-8 h-8 rounded-full object-cover shrink-0"
                                            alt="{{ $customer->name }}">
                                    @else
                                        <div
                                            class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                                            <span class="text-xs font-bold text-indigo-600">
                                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                    <div>
                                        <a href="{{ route('customers.show', $customer) }}" wire:navigate
                                            class="font-semibold text-indigo-600 hover:text-indigo-800 text-sm">
                                            {{ $customer->name }}
                                        </a>
                                        @if ($customer->district)
                                            <div class="text-xs text-gray-400">{{ $customer->district }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="table-td">
                                <div class="text-sm">{{ $customer->phone }}</div>
                                @if ($customer->phone_alt)
                                    <div class="text-xs text-gray-400">{{ $customer->phone_alt }}</div>
                                @endif
                            </td>
                            <td class="table-td">
                                <span class="badge {{ $customer->customer_type->badgeClass() }}">
                                    {{ $customer->customer_type->label() }}
                                </span>
                            </td>
                            <td class="table-td">
                                @if ($customer->current_balance > 0)
                                    <span
                                        class="font-bold text-red-600">৳{{ number_format($customer->current_balance, 2) }}</span>
                                    @if ($customer->credit_limit > 0)
                                        <div class="text-xs text-gray-400">Limit:
                                            ৳{{ number_format($customer->credit_limit, 0) }}</div>
                                    @endif
                                @else
                                    <span class="text-green-600 font-medium text-sm">Clear</span>
                                @endif
                            </td>
                            <td class="table-td text-gray-700">
                                ৳{{ number_format($customer->total_purchase_amount, 0) }}
                            </td>
                            <td class="table-td">
                                @if ($customer->guarantor)
                                    <div class="text-xs">
                                        <span class="font-medium text-gray-700">{{ $customer->guarantor->name }}</span>
                                        <div class="text-gray-400">{{ $customer->guarantor->phone }}</div>
                                    </div>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td class="table-td">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('customers.show', $customer) }}" wire:navigate
                                        class="text-xs text-indigo-600 hover:underline font-medium">Profile</a>
                                    <a href="{{ route('customers.edit', $customer) }}" wire:navigate
                                        class="text-xs text-gray-500 hover:underline font-medium">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="table-td text-center text-gray-400 py-12">
                                No customers yet.
                                <a href="{{ route('customers.create') }}" wire:navigate
                                    class="text-indigo-600 hover:underline">Add one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($customers->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $customers->links() }}</div>
        @endif
    </div>
</div>
