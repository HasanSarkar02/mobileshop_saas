<div class="max-w-2xl mx-auto space-y-5">

    <div class="card p-5">
        <h2 class="text-xl font-bold text-gray-900">Opening Balance Setup</h2>
        <p class="text-sm text-gray-500 mt-1">
            Enter the cash and bank balances you have when starting to use the ERP.
            Each entry creates an accounting journal (Dr Account / Cr Opening Balance Equity).
        </p>
    </div>

    {{-- Warning --}}
    <div class="card p-4 bg-amber-50 border-amber-200">
        <div class="text-sm text-amber-800 space-y-1">
            <p><strong>⚠ Do this ONCE before recording any sales or expenses.</strong></p>
            <p>Opening balances set the starting point of your accounting records.
                Changing them later requires a reversal and re-entry.</p>
        </div>
    </div>

    {{-- As Of Date --}}
    <div class="card p-5">
        <div class="flex items-end gap-4">
            <div>
                <label class="label">Balances As Of Date *</label>
                <input wire:model="asOfDate" type="date" class="input w-40">
                <p class="text-xs text-gray-400 mt-0.5">Usually the day before you start using this ERP.</p>
            </div>
        </div>
    </div>

    {{-- Account List --}}
    <div class="space-y-3">
        @foreach ($this->allAccounts as $acc)
            @php
                $alreadySet = in_array($acc->id, $this->existingOpeningBalances);
                $justSaved = in_array($acc->id, $saved);
            @endphp
            <div class="card p-4 {{ $alreadySet ? 'border-green-200 bg-green-50' : '' }}"
                wire:key="acc-{{ $acc->id }}">
                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <div class="font-semibold text-gray-900">{{ $acc->name }}</div>
                        <div class="text-xs text-gray-400 capitalize">{{ $acc->provider ?? 'other' }}</div>
                    </div>

                    @if ($alreadySet)
                        <div class="flex items-center gap-2 text-green-700">
                            <span class="badge badge-green">✓ Opening balance set</span>
                            <span class="text-xs text-gray-400">
                                (reverse from Treasury to change)
                            </span>
                        </div>
                    @else
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">৳</span>
                                <input wire:model="balances.{{ $acc->id }}" type="number" step="0.01"
                                    min="0" class="input pl-7 w-40 text-sm font-semibold" placeholder="0.00">
                            </div>
                            <button wire:click="saveAccount({{ $acc->id }})" wire:loading.attr="disabled"
                                wire:target="saveAccount({{ $acc->id }})"
                                class="btn-primary btn-sm whitespace-nowrap">
                                <span wire:loading.remove wire:target="saveAccount({{ $acc->id }})">Set
                                    Balance</span>
                                <span wire:loading wire:target="saveAccount({{ $acc->id }})">Saving…</span>
                            </button>
                        </div>
                    @endif
                </div>
                @error("balances.{$acc->id}")
                    <p class="error mt-1 ml-0">{{ $message }}</p>
                @enderror
            </div>
        @endforeach
    </div>

    {{-- Done Button --}}
    @if (count($this->existingOpeningBalances) > 0)
        <div class="flex justify-between items-center pb-8">
            <p class="text-sm text-gray-500">
                {{ count($this->existingOpeningBalances) }} of {{ $this->allAccounts->count() }} accounts configured.
            </p>
            <a href="{{ route('treasury.index') }}" wire:navigate class="btn-primary">
                Done — Go to Treasury →
            </a>
        </div>
    @endif
</div>
