<div class="max-w-4xl mx-auto space-y-6">
    {{-- Header Card --}}
    <div class="card p-6 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex-1">
            <h2 class="text-xl font-bold text-gray-900">{{ $shop->name }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $shop->email }} · {{ $shop->slug }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @include('partials.shop-status-badge', ['status' => $shop->status])
            @unless ($showEditForm)
                <button wire:click="startEdit" class="btn-secondary btn-sm">✏ Edit Info</button>
            @endunless
            <a href="{{ route('admin.shop-features', $shop) }}" wire:navigate class="btn-secondary btn-sm">
                🔒 Manage Features
            </a>
            @if ($shop->is_active)
                <button wire:click="openSuspendModal" class="btn btn-sm btn-danger">Suspend</button>
            @else
                <button wire:click="activate" class="btn btn-sm btn-success">Activate</button>
            @endif
            <button wire:click="impersonate" class="btn btn-sm btn-secondary">Login as Owner</button>
        </div>
    </div>

    {{-- Overview Stats --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        @foreach ([['label' => 'Users', 'val' => $stats->users, 'color' => 'indigo'], ['label' => 'Branches', 'val' => $shop->branches->count(), 'color' => 'blue'], ['label' => 'Products', 'val' => $stats->products, 'color' => 'green'], ['label' => 'Confirmed Sales', 'val' => $stats->sales_count, 'color' => 'gray'], ['label' => 'Sales Total (৳)', 'val' => number_format($stats->sales_total, 0), 'color' => 'emerald']] as $s)
            <div class="card p-3 border-0 bg-{{ $s['color'] }}-50">
                <div class="text-lg font-bold text-{{ $s['color'] }}-700">{{ $s['val'] }}</div>
                <div class="text-xs font-medium text-{{ $s['color'] }}-500 mt-0.5">{{ $s['label'] }}</div>
            </div>
        @endforeach
        <div class="card p-3 border-0 bg-amber-50">
            <div class="text-lg font-bold text-amber-700">৳{{ number_format($stats->customer_due, 0) }}</div>
            <div class="text-xs font-medium text-amber-500 mt-0.5">Total Customer Due</div>
        </div>
    </div>

    {{-- Subscription / Expiration --}}
    <div class="card p-5 space-y-3">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
            <h3 class="font-semibold text-gray-900 text-sm">Subscription</h3>
            @if (!$editingSubscription && $shop->activeSubscription)
                <button wire:click="startEditSubscription"
                    class="text-xs text-indigo-600 hover:underline font-medium">Edit Dates</button>
            @endif
        </div>

        @if ($shop->activeSubscription)
            @if ($editingSubscription)
                <div class="grid sm:grid-cols-3 gap-4">
                    <div>
                        <label class="label text-xs">Trial Ends At</label>
                        <input wire:model="subTrialEndsAt" type="date" class="input text-sm">
                    </div>
                    <div>
                        <label class="label text-xs">Current Period End</label>
                        <input wire:model="subCurrentPeriodEnd" type="date" class="input text-sm">
                    </div>
                    <div>
                        <label class="label text-xs">Next Billing Date</label>
                        <input wire:model="subNextBillingDate" type="date" class="input text-sm">
                    </div>
                </div>
                <div class="flex gap-3">
                    <button wire:click="saveSubscriptionDates" class="btn-primary btn-sm">Save</button>
                    <button wire:click="$set('editingSubscription', false)" class="btn-secondary btn-sm">Cancel</button>
                </div>
            @else
                <div class="grid sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <div class="text-xs text-gray-400">Plan</div>
                        <div class="font-semibold">{{ $shop->activeSubscription->plan?->name ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">Status</div>
                        <span class="badge {{ $shop->activeSubscription->statusBadgeClass() }} text-xs">
                            {{ ucfirst($shop->activeSubscription->status) }}
                        </span>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">Trial Ends</div>
                        <div>{{ $shop->activeSubscription->trial_ends_at?->format('d M Y') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-gray-400">Next Billing / Expiry</div>
                        <div
                            class="font-semibold {{ $shop->activeSubscription->next_billing_date?->isPast() ? 'text-red-600' : '' }}">
                            {{ $shop->activeSubscription->next_billing_date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>
                </div>
            @endif
        @else
            <p class="text-sm text-gray-400">No active subscription assigned.</p>
        @endif
    </div>

    {{-- Books Lock --}}
    <div class="card p-5 space-y-3">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
            <h3 class="font-semibold text-gray-900 text-sm">Accounting Period Lock</h3>
            @unless ($editingBooksLock)
                <button wire:click="startEditBooksLock"
                    class="text-xs text-indigo-600 hover:underline font-medium">Edit</button>
            @endunless
        </div>
        @if ($editingBooksLock)
            <div class="flex items-center gap-3">
                <input wire:model="booksLockedThroughInput" type="date" class="input text-sm w-auto">
                <button wire:click="saveBooksLock" class="btn-primary btn-sm">Save</button>
                <button wire:click="$set('editingBooksLock', false)" class="btn-secondary btn-sm">Cancel</button>
            </div>
            <p class="text-xs text-gray-400">Leave blank to fully unlock. Entries dated on/before this date cannot be
                posted.</p>
        @else
            <p class="text-sm">
                {{ $shop->books_locked_through ? 'Locked through ' . $shop->books_locked_through->format('d M Y') : 'Not locked — all periods open.' }}
            </p>
        @endif
    </div>

    {{-- Activity Log --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900 text-sm">Recent Admin Activity</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse ($activityLogs as $log)
                <div class="px-5 py-3 text-sm">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-800">{{ str_replace('.', ' ', $log->action) }}</span>
                        <span class="text-xs text-gray-400">{{ $log->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <div class="text-xs text-gray-500 mt-0.5">
                        By {{ $log->admin?->name ?? 'System' }}
                        @if ($log->reason)
                            — {{ $log->reason }}
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-5 py-6 text-center text-gray-400 text-sm">No activity recorded yet.</div>
            @endforelse
        </div>
    </div>

    @if ($showEditForm)
        {{-- ── Edit Form ── --}}
        <div class="card p-6 border-2 border-indigo-200 space-y-5">
            <h3 class="font-semibold text-gray-900">Edit Shop Information</h3>

            <fieldset class="space-y-4">
                <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Shop Details</legend>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label text-xs">Shop Name *</label>
                        <input wire:model="name" type="text" class="input @error('name') input-error @enderror">
                        @error('name')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">Contact Email *</label>
                        <input wire:model="email" type="email" class="input @error('email') input-error @enderror">
                        @error('email')
                            <p class="error">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-400 mt-1">Shop's contact email — separate from the owner's login
                            email below.</p>
                    </div>
                    <div>
                        <label class="label text-xs">Phone</label>
                        <input wire:model="phone" type="text" class="input @error('phone') input-error @enderror">
                        @error('phone')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">Business Type *</label>
                        <select wire:model="businessType" class="input">
                            <option value="mobile_shop">Mobile Shop</option>
                            <option value="electronics">Electronics</option>
                            <option value="general_retail">General Retail</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="label text-xs">Address</label>
                    <textarea wire:model="address" rows="2" class="input @error('address') input-error @enderror"></textarea>
                    @error('address')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
            </fieldset>

            <hr class="border-gray-100">

            {{-- Owner Account --}}
            <fieldset class="space-y-4">
                <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Owner Account</legend>
                @if ($ownerId)
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label text-xs">Owner Name *</label>
                            <input wire:model="ownerName" type="text"
                                class="input @error('ownerName') input-error @enderror">
                            @error('ownerName')
                                <p class="error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="label text-xs">Owner Login Email *</label>
                            <input wire:model="ownerEmail" type="email"
                                class="input @error('ownerEmail') input-error @enderror">
                            @error('ownerEmail')
                                <p class="error">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-gray-400 mt-1">This is what the owner uses to sign in.</p>
                        </div>
                        <div>
                            <label class="label text-xs">Owner Phone</label>
                            <input wire:model="ownerPhone" type="text"
                                class="input @error('ownerPhone') input-error @enderror">
                            @error('ownerPhone')
                                <p class="error">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="flex items-end">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input wire:model="ownerIsActive" type="checkbox"
                                    class="rounded border-gray-300 text-indigo-600">
                                <span class="text-sm text-gray-700">Owner account active</span>
                            </label>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-400">No owner assigned to this shop yet.</p>
                @endif
            </fieldset>

            <hr class="border-gray-100">

            <fieldset class="space-y-4">
                <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Trial</legend>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label text-xs">Trial Ends On</label>
                        <input wire:model="trialEndsAt" type="date"
                            class="input @error('trialEndsAt') input-error @enderror">
                        @error('trialEndsAt')
                            <p class="error">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-400 mt-1">
                            Extending this date does not automatically reactivate an Expired shop — use the
                            Activate button separately if needed.
                        </p>
                    </div>
                </div>
            </fieldset>

            <hr class="border-gray-100">

            <fieldset class="space-y-4">
                <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wider">VAT</legend>
                <div class="flex items-center gap-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input wire:model.live="vatEnabled" type="checkbox" class="sr-only peer">
                        <div
                            class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 peer-checked:after:translate-x-4 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-transform">
                        </div>
                    </label>
                    <span class="text-sm text-gray-700">Enable VAT for this shop</span>
                </div>
                @if ($vatEnabled)
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label text-xs">VAT Registration Number</label>
                            <input wire:model="vatRegistrationNumber" type="text" class="input">
                        </div>
                        <div>
                            <label class="label text-xs">Default VAT Rate (%)</label>
                            <input wire:model="defaultVatRate" type="number" step="0.01" min="0"
                                max="100" class="input @error('defaultVatRate') input-error @enderror">
                            @error('defaultVatRate')
                                <p class="error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif
            </fieldset>

            <hr class="border-gray-100">

            <fieldset class="space-y-4">
                <legend class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Documents & Branding
                </legend>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label text-xs">Website</label>
                        <input wire:model="website" type="text" placeholder="https://…"
                            class="input @error('website') input-error @enderror">
                        @error('website')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">Trade License Number</label>
                        <input wire:model="tradeLicenseNumber" type="text"
                            class="input @error('tradeLicenseNumber') input-error @enderror">
                        @error('tradeLicenseNumber')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div>
                    <label class="label text-xs">Document Footer Note</label>
                    <textarea wire:model="documentFooterNote" rows="2"
                        class="input @error('documentFooterNote') input-error @enderror"
                        placeholder="Shown at the bottom of invoices / receipts…"></textarea>
                    @error('documentFooterNote')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input wire:model="showDocumentConfidential" type="checkbox"
                        class="rounded border-gray-300 text-indigo-600">
                    <span class="text-sm text-gray-700">Show "Confidential" watermark on documents</span>
                </label>
            </fieldset>

            <div class="flex gap-3 pt-2">
                <button wire:click="updateShop" class="btn-primary btn-sm" wire:loading.attr="disabled"
                    wire:target="updateShop">
                    <span wire:loading.remove wire:target="updateShop">Save Changes</span>
                    <span wire:loading wire:target="updateShop">Saving…</span>
                </button>
                <button wire:click="cancelEdit" class="btn-secondary btn-sm">Cancel</button>
            </div>
        </div>
    @else
        <div class="grid sm:grid-cols-2 gap-6">
            {{-- Shop Info --}}
            <div class="card p-5 space-y-3">
                <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">Shop Details</h3>
                @foreach ([['label' => 'Business Type', 'value' => ucfirst(str_replace('_', ' ', $shop->business_type))], ['label' => 'Phone', 'value' => $shop->phone ?? '—'], ['label' => 'Address', 'value' => $shop->address ?? '—'], ['label' => 'Trial Ends', 'value' => $shop->trial_ends_at?->format('d M Y') ?? '—'], ['label' => 'VAT', 'value' => $shop->vat_enabled ? $shop->vat_registration_number . ' (' . $shop->default_vat_rate . '%)' : 'Disabled'], ['label' => 'Website', 'value' => $shop->website ?? '—'], ['label' => 'Trade License', 'value' => $shop->trade_license_number ?? '—']] as $row)
                    <div class="flex gap-3">
                        <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">{{ $row['label'] }}</span>
                        <span class="text-sm text-gray-800">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Owner Info --}}
            <div class="card p-5 space-y-3">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                    <h3 class="font-semibold text-gray-900 text-sm">Owner</h3>
                    @if ($shop->owner && !$shop->owner->password_changed_at)
                        <button wire:click="resendOwnerInvite"
                            wire:confirm="Resend the password-setup email to {{ $shop->owner->email }}?"
                            class="text-xs text-indigo-600 hover:underline font-medium">
                            Resend Invite
                        </button>
                    @endif
                </div>
                @if ($shop->owner)
                    @foreach ([['label' => 'Name', 'value' => $shop->owner->name], ['label' => 'Email', 'value' => $shop->owner->email], ['label' => 'Phone', 'value' => $shop->owner->phone ?? '—'], ['label' => 'Account', 'value' => $shop->owner->is_active ? 'Active' : 'Deactivated'], ['label' => 'Last Login', 'value' => $shop->owner->last_login_at?->format('d M Y H:i') ?? 'Never'], ['label' => 'Password Set', 'value' => $shop->owner->password_changed_at ? $shop->owner->password_changed_at->format('d M Y') : 'Not yet']] as $row)
                        <div class="flex gap-3">
                            <span class="text-xs text-gray-400 w-28 shrink-0 pt-0.5">{{ $row['label'] }}</span>
                            <span class="text-sm text-gray-800">{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                @else
                    <p class="text-sm text-gray-400">No owner yet.</p>
                @endif
            </div>
        </div>

        {{-- Branches --}}
        <div class="card overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100">
                <h3 class="font-semibold text-gray-900 text-sm">Branches ({{ $shop->branches->count() }})</h3>
            </div>
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="table-th">Name</th>
                        <th class="table-th">Code</th>
                        <th class="table-th">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($shop->branches as $branch)
                        <tr>
                            <td class="table-td font-medium">{{ $branch->name }}@if ($branch->is_main)
                                    <span class="badge badge-blue ml-1">Main</span>
                                @endif
                            </td>
                            <td class="table-td text-gray-500">{{ $branch->code }}</td>
                            <td class="table-td"><span
                                    class="{{ $branch->is_active ? 'badge-green' : 'badge-gray' }} badge">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($showSuspendModal)
        <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4">
                <h3 class="font-bold text-gray-900">Suspend {{ $shop->name }}</h3>
                <div>
                    <label class="label text-xs">Reason *</label>
                    <textarea wire:model="suspendReason" rows="3" class="input @error('suspendReason') input-error @enderror"></textarea>
                    @error('suspendReason')
                        <p class="error">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex gap-3">
                    <button wire:click="confirmSuspend" class="btn-primary flex-1">Confirm Suspend</button>
                    <button wire:click="$set('showSuspendModal', false)" class="btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    @endif
</div>
