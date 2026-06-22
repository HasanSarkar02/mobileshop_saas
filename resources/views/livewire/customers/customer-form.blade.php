<div class="max-w-3xl mx-auto space-y-5">
    <div class="card overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-900">{{ $customer?->exists ? 'Edit Customer' : 'New Customer' }}</h2>
        </div>

        <form wire:submit="save" class="p-6 space-y-8">

            {{-- ── Section 1: Basic Info ── --}}
            <fieldset class="space-y-4">
                <legend
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-2 block w-full border-b border-gray-100">
                    Basic Information
                </legend>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="label">Customer Type *</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach (\App\Enums\CustomerType::cases() as $ct)
                                @if ($ct !== \App\Enums\CustomerType::WalkIn)
                                    <label
                                        class="flex items-center gap-2 px-3 py-2 rounded-lg border-2 cursor-pointer text-sm transition-colors
                                        {{ $customerType === $ct->value ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-200 text-gray-600 hover:border-gray-300' }}">
                                        <input wire:model.live="customerType" type="radio" value="{{ $ct->value }}"
                                            class="sr-only">
                                        {{ $ct->label() }}
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="label">Full Name *</label>
                        <input wire:model="name" type="text" class="input @error('name') input-error @enderror"
                            placeholder="Customer name">
                        @error('name')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Primary Phone *</label>
                        <input wire:model="phone" type="tel" class="input @error('phone') input-error @enderror"
                            placeholder="01XXXXXXXXX">
                        @error('phone')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Alt. Phone</label>
                        <input wire:model="phoneAlt" type="tel" class="input" placeholder="01XXXXXXXXX (optional)">
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input wire:model="email" type="email" class="input" placeholder="optional">
                        @error('email')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Address</label>
                        <textarea wire:model="address" rows="2" class="input" placeholder="Full address…"></textarea>
                    </div>
                    <div>
                        <label class="label">District</label>
                        <input wire:model="district" type="text" class="input" placeholder="e.g. Dhaka">
                    </div>
                    <div>
                        <label class="label">Thana / Upazila</label>
                        <input wire:model="thana" type="text" class="input" placeholder="e.g. Gulshan">
                    </div>
                    <div>
                        <label class="label">Date of Birth</label>
                        <input wire:model="dateOfBirth" type="date" class="input">
                    </div>
                    <div>
                        <label class="label">Gender</label>
                        <select wire:model="gender" class="input">
                            <option value="">Select…</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Occupation</label>
                        <input wire:model="occupation" type="text" class="input" placeholder="e.g. Service holder">
                    </div>
                    <div>
                        <label class="label">Credit Limit (৳)</label>
                        <input wire:model="creditLimit" type="number" min="0" step="500" class="input"
                            placeholder="0 = unlimited">
                        <p class="text-xs text-gray-400 mt-0.5">Set 0 for no limit</p>
                    </div>
                    @if (!$customer?->exists)
                        <div>
                            <label class="label">Opening Balance (৳)
                                <span class="text-xs font-normal text-gray-400">— existing due before joining</span>
                            </label>
                            <input wire:model="openingBalance" type="number" min="0" step="0.01"
                                class="input" placeholder="0">
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <label class="label">Internal Notes</label>
                        <textarea wire:model="notes" rows="2" class="input" placeholder="Internal notes (not shown to customer)…"></textarea>
                    </div>
                </div>
            </fieldset>

            {{-- ── Section 2: Photo ── --}}
            <fieldset class="space-y-4">
                <legend
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-2 block w-full border-b border-gray-100">
                    Customer Photo
                </legend>
                <div class="flex items-start gap-5">
                    {{-- Current / preview --}}
                    <div class="shrink-0">
                        @if ($photo)
                            <img src="{{ $photo->temporaryUrl() }}"
                                class="w-24 h-24 rounded-xl object-cover border-2 border-indigo-300" alt="Preview">
                        @elseif($customer?->photo_path)
                            <img src="{{ Storage::url($customer->photo_path) }}"
                                class="w-24 h-24 rounded-xl object-cover border-2 border-gray-200" alt="Photo">
                        @else
                            <div
                                class="w-24 h-24 rounded-xl bg-gray-100 border-2 border-dashed border-gray-300 flex items-center justify-center">
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <label class="label">Upload Photo</label>
                        <input wire:model="photo" type="file" accept="image/*"
                            class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                        @error('photo')
                            <p class="error">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP. Max 3MB.</p>
                        <div wire:loading wire:target="photo" class="text-xs text-indigo-500 mt-1">Uploading…</div>
                    </div>
                </div>
            </fieldset>

            {{-- ── Section 3: ID Documents ── --}}
            <fieldset class="space-y-4">
                <legend
                    class="text-xs font-semibold text-gray-400 uppercase tracking-wider pb-2 block w-full border-b border-gray-100">
                    Identity Documents
                </legend>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">ID Type</label>
                        <select wire:model="idType" class="input">
                            <option value="">Select ID type…</option>
                            @foreach (\App\Enums\CustomerIdType::cases() as $idT)
                                <option value="{{ $idT->value }}">{{ $idT->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">ID Number</label>
                        <input wire:model="idNumber" type="text" class="input" placeholder="Enter ID number">
                    </div>
                    {{-- ID Front --}}
                    <div>
                        <label class="label">ID Front Photo</label>
                        @if ($idFront)
                            <img src="{{ $idFront->temporaryUrl() }}"
                                class="h-20 rounded-lg object-cover mb-2 border">
                        @elseif($customer?->id_front_path)
                            <img src="{{ Storage::url($customer->id_front_path) }}"
                                class="h-20 rounded-lg object-cover mb-2 border">
                        @endif
                        <input wire:model="idFront" type="file" accept="image/*"
                            class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-gray-50 file:text-gray-700 cursor-pointer">
                        @error('idFront')
                            <p class="error">{{ $message }}</p>
                        @enderror
                        <div wire:loading wire:target="idFront" class="text-xs text-indigo-500 mt-1">Uploading…</div>
                    </div>
                    {{-- ID Back --}}
                    <div>
                        <label class="label">ID Back Photo</label>
                        @if ($idBack)
                            <img src="{{ $idBack->temporaryUrl() }}"
                                class="h-20 rounded-lg object-cover mb-2 border">
                        @elseif($customer?->id_back_path)
                            <img src="{{ Storage::url($customer->id_back_path) }}"
                                class="h-20 rounded-lg object-cover mb-2 border">
                        @endif
                        <input wire:model="idBack" type="file" accept="image/*"
                            class="block w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-gray-50 file:text-gray-700 cursor-pointer">
                        @error('idBack')
                            <p class="error">{{ $message }}</p>
                        @enderror
                        <div wire:loading wire:target="idBack" class="text-xs text-indigo-500 mt-1">Uploading…</div>
                    </div>
                </div>
            </fieldset>

            {{-- ── Section 4: Guarantor ── --}}
            <fieldset class="space-y-4">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                    <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wider">
                        Guarantor / Jamindar
                    </legend>
                    <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                        <input wire:model.live="hasGuarantor" type="checkbox"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Add guarantor
                    </label>
                </div>

                <div wire:show="hasGuarantor" class="space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label">Guarantor Name *</label>
                            <input wire:model="guarantorName" type="text"
                                class="input @error('guarantorName') input-error @enderror">
                            @error('guarantorName')
                                <p class="error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="label">Guarantor Phone *</label>
                            <input wire:model="guarantorPhone" type="tel"
                                class="input @error('guarantorPhone') input-error @enderror">
                            @error('guarantorPhone')
                                <p class="error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="label">Alt Phone</label>
                            <input wire:model="guarantorPhoneAlt" type="tel" class="input">
                        </div>
                        <div>
                            <label class="label">Relation</label>
                            <select wire:model="guarantorRelation" class="input">
                                @foreach (\App\Enums\GuarantorRelation::cases() as $rel)
                                    <option value="{{ $rel->value }}">{{ $rel->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Address</label>
                            <input wire:model="guarantorAddress" type="text" class="input">
                        </div>
                        <div>
                            <label class="label">NID Number</label>
                            <input wire:model="guarantorNid" type="text" class="input">
                        </div>
                    </div>
                    {{-- Guarantor photos --}}
                    <div class="grid sm:grid-cols-3 gap-4">
                        @foreach ([['model' => 'guarantorPhoto', 'label' => 'Guarantor Photo', 'target' => 'guarantorPhoto'], ['model' => 'guarantorNidFront', 'label' => 'Guarantor NID Front', 'target' => 'guarantorNidFront'], ['model' => 'guarantorNidBack', 'label' => 'Guarantor NID Back', 'target' => 'guarantorNidBack']] as $field)
                            <div>
                                <label class="label text-xs">{{ $field['label'] }}</label>
                                @if ($this->{$field['model']})
                                    <img src="{{ $this->{$field['model']}->temporaryUrl() }}"
                                        class="h-16 rounded-lg object-cover mb-1 border w-full">
                                @endif
                                <input wire:model="{{ $field['model'] }}" type="file" accept="image/*"
                                    class="block w-full text-xs text-gray-500 file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-gray-50 file:text-gray-700 cursor-pointer">
                                @error($field['model'])
                                    <p class="error">{{ $message }}</p>
                                @enderror
                                <div wire:loading wire:target="{{ $field['target'] }}"
                                    class="text-xs text-indigo-500">Uploading…</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </fieldset>

            {{-- Submit --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading.remove wire:target="save">
                        {{ $customer?->exists ? 'Update Customer' : 'Create Customer' }}
                    </span>
                    <span wire:loading wire:target="save">Saving…</span>
                </button>
                <a href="{{ route('customers.index') }}" wire:navigate class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
