<div class="max-w-4xl mx-auto space-y-5">
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">
                {{ $ticket?->exists ? "Edit Ticket — {$ticket->ticket_number}" : 'New Service Ticket' }}
            </h2>
        </div>
        <form wire:submit="save" class="p-6 space-y-6">

            {{-- Customer --}}
            <fieldset class="space-y-4">
                <legend
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-2 block w-full border-b border-gray-100">
                    Customer</legend>
                <div class="relative" @click.outside="$wire.showCustDrop = false">
                    <label class="label text-xs">Search Existing Customer</label>
                    <input wire:model.live.debounce.300ms="customerSearch" type="search" placeholder="Name or phone…"
                        class="input text-sm" autocomplete="off">
                    <div wire:show="showCustDrop"
                        class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                        @foreach ($customerResults as $c)
                            <button type="button"
                                wire:click="selectCustomer({{ $c['id'] }}, '{{ addslashes($c['name']) }}', '{{ $c['phone'] }}')"
                                class="w-full flex items-center justify-between px-4 py-2.5 hover:bg-indigo-50 text-left text-sm border-b border-gray-50 last:border-0">
                                <span class="font-medium">{{ $c['name'] }}</span>
                                <span class="text-gray-400">{{ $c['phone'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label text-xs">Customer Name * <span class="text-gray-400 font-normal">(can be
                                walk-in)</span></label>
                        <input wire:model="customerName" type="text"
                            class="input text-sm @error('customerName') input-error @enderror"
                            placeholder="Customer name">
                        @error('customerName')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">Phone</label>
                        <input wire:model="customerPhone" type="tel" class="input text-sm"
                            placeholder="01XXXXXXXXX">
                    </div>
                </div>
            </fieldset>

            {{-- Device --}}
            <fieldset class="space-y-4">
                <legend
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-2 block w-full border-b border-gray-100">
                    Device Information</legend>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div>
                        <label class="label text-xs">Brand</label>
                        <input wire:model="deviceBrand" type="text" class="input text-sm"
                            placeholder="Samsung, Apple…">
                    </div>
                    <div>
                        <label class="label text-xs">Model</label>
                        <input wire:model="deviceModel" type="text" class="input text-sm"
                            placeholder="Galaxy A52, iPhone 13…">
                    </div>
                    <div>
                        <label class="label text-xs">IMEI (optional)</label>
                        <input wire:model="deviceImei" type="text" class="input text-sm font-mono"
                            placeholder="15-digit IMEI">
                    </div>
                    <div>
                        <label class="label text-xs">Color</label>
                        <input wire:model="deviceColor" type="text" class="input text-sm" placeholder="Black, Blue…">
                    </div>
                    <div>
                        <label class="label text-xs">Physical Condition</label>
                        <input wire:model="deviceCondition" type="text" class="input text-sm"
                            placeholder="Cracked screen, minor scratch…">
                    </div>
                </div>
            </fieldset>

            {{-- Service --}}
            <fieldset class="space-y-4">
                <legend
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-2 block w-full border-b border-gray-100">
                    Service Details</legend>
                <div>
                    <label class="label text-xs">Problem Description *</label>
                    <textarea wire:model="problemDescription" rows="3"
                        class="input text-sm @error('problemDescription') input-error @enderror"
                        placeholder="Describe what the customer says is wrong…"></textarea>
                    @error('problemDescription')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label text-xs">Diagnosis Notes (internal)</label>
                    <textarea wire:model="diagnosisNotes" rows="2" class="input text-sm" placeholder="Technician's diagnosis…"></textarea>
                </div>
                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label text-xs">Estimated Cost (৳)</label>
                        <input wire:model="estimatedCost" type="number" min="0" step="0.01"
                            class="input text-sm">
                    </div>
                    <div>
                        <label class="label text-xs">Labor Charge (৳) *</label>
                        <input wire:model.live="laborCharge" type="number" min="0" step="0.01"
                            class="input text-sm @error('laborCharge') input-error @enderror">
                        @error('laborCharge')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">Technician</label>
                        <select wire:model="technicianId" class="input text-sm">
                            <option value="0">Unassigned</option>
                            @foreach ($this->technicians as $t)
                                <option value="{{ $t->id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label text-xs">Branch</label>
                        <select wire:model="branchId" class="input text-sm">
                            @foreach ($this->branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="flex items-center gap-2 cursor-pointer mt-5">
                            <input wire:model="isWarrantyService" type="checkbox"
                                class="rounded border-gray-300 text-indigo-600">
                            <span class="text-sm text-gray-700">Warranty Service (no charge)</span>
                        </label>
                    </div>
                </div>
            </fieldset>

            {{-- Parts --}}
            <fieldset class="space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                    <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Parts Used</legend>
                    <div class="flex gap-2">
                        <button type="button" wire:click="addExternalPart" class="btn-secondary btn-sm">+ External
                            Part</button>
                    </div>
                </div>

                {{-- Inventory part search --}}
                <div class="relative" @click.outside="$wire.showPartDrop = false">
                    <input wire:model.live.debounce.300ms="partSearch" type="search"
                        placeholder="Search from inventory (non-serialized parts)…" class="input text-sm"
                        autocomplete="off">
                    <div wire:show="showPartDrop"
                        class="absolute z-20 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden">
                        @foreach ($partResults as $r)
                            <button type="button"
                                wire:click="addPartFromInventory({{ $r['id'] }}, '{{ addslashes($r['label']) }}')"
                                class="w-full flex items-center gap-3 px-4 py-2.5 hover:bg-indigo-50 text-left text-sm border-b border-gray-50 last:border-0">
                                <span class="font-medium">{{ $r['label'] }}</span>
                                <span class="text-xs text-gray-400 ml-auto">{{ $r['sku'] }}</span>
                                <span class="badge badge-blue text-xs">Stock</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                @foreach ($parts as $idx => $part)
                    <div class="grid sm:grid-cols-5 gap-2 items-start" wire:key="part-{{ $idx }}">
                        <div class="sm:col-span-2">
                            <input wire:model="parts.{{ $idx }}.part_description" type="text"
                                class="input text-sm" placeholder="Part name / description">
                            @if ($part['from_inventory'])
                                <span class="text-xs text-indigo-500">From inventory</span>
                            @else
                                <span class="text-xs text-amber-500">External purchase</span>
                            @endif
                        </div>
                        <div>
                            <input wire:model.live="parts.{{ $idx }}.quantity" type="number"
                                min="1" class="input text-sm" placeholder="Qty">
                        </div>
                        <div>
                            <input wire:model.live="parts.{{ $idx }}.unit_cost" type="number"
                                step="0.01" min="0" class="input text-sm" placeholder="Unit cost ৳">
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900">
                                ৳{{ number_format($part['line_total'] ?? 0, 0) }}
                            </span>
                            <button type="button" wire:click="removePart({{ $idx }})"
                                class="text-gray-300 hover:text-red-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach

                {{-- Total Estimate --}}
                @if (!empty($parts) || (float) $laborCharge > 0)
                    <div class="bg-gray-50 rounded-xl p-3 text-sm space-y-1">
                        <div class="flex justify-between text-gray-600">
                            <span>Parts Total</span>
                            <span>৳{{ number_format($this->totalPartsEstimate, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Labor Charge</span>
                            <span>৳{{ number_format((float) $laborCharge, 2) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-base border-t border-gray-200 pt-1">
                            <span>Total Charge</span>
                            <span class="text-indigo-700">৳{{ number_format($this->totalEstimate, 2) }}</span>
                        </div>
                    </div>
                @endif
            </fieldset>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $ticket?->exists ? 'Update Ticket' : 'Create Ticket' }}</span>
                    <span wire:loading>Saving…</span>
                </button>
                <a href="{{ route('service.index') }}" wire:navigate class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
