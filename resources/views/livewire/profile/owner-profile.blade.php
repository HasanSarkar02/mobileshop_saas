<div class="max-w-2xl mx-auto space-y-5">

    <h2 class="text-xl font-bold text-gray-900">My Profile</h2>

    {{-- Avatar + Role --}}
    <div class="card p-5 flex items-center gap-5">
        <div class="w-16 h-16 rounded-2xl bg-indigo-100 flex items-center justify-center shrink-0">
            <span class="text-2xl font-bold text-indigo-600">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </span>
        </div>
        <div>
            <div class="text-lg font-bold text-gray-900">{{ $user->name }}</div>
            <div class="text-sm text-gray-500">{{ $user->email }}</div>
            <div class="flex gap-2 mt-1 flex-wrap">
                <span class="badge badge-indigo">{{ ucfirst($user->user_type->value) }}</span>
                @if ($user->shop)
                    <span class="badge badge-gray">{{ $user->shop->name }}</span>
                @endif
                @if ($user->branch)
                    <span class="badge badge-gray">{{ $user->branch->name }}</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Account Info --}}
    <div class="card p-5">
        <h3 class="font-semibold text-gray-900 text-sm mb-4">Account Information</h3>
        <div class="space-y-3">
            <div>
                <label class="label">Full Name *</label>
                <input wire:model="name" type="text" class="input @error('name') input-error @enderror">
                @error('name')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label">Email (Login) *</label>
                <div class="flex gap-2">
                    <input wire:model="email" type="email" class="input flex-1 @error('email') input-error @enderror"
                        wire:focus="$set('changingEmail', true)">
                    @if ($changingEmail && $email !== auth()->user()->email)
                        <span class="text-xs text-amber-600 self-center whitespace-nowrap">⚠ Changed</span>
                    @endif
                </div>
                @error('email')
                    <p class="error">{{ $message }}</p>
                @enderror

                @if ($changingEmail && $email !== auth()->user()->email)
                    <div class="mt-2">
                        <label class="label text-xs">Confirm with your current password *</label>
                        <input wire:model="passwordForEmail" type="password"
                            class="input @error('passwordForEmail') input-error @enderror"
                            placeholder="Enter password to confirm email change">
                        @error('passwordForEmail')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            </div>
            <div>
                <label class="label">Phone</label>
                <input wire:model="phone" type="tel" class="input" placeholder="01XXXXXXXXX">
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-gray-100">
            <button wire:click="saveInfo" class="btn-primary btn-sm" wire:loading.attr="disabled"
                wire:target="saveInfo">
                <span wire:loading.remove wire:target="saveInfo">Save Changes</span>
                <span wire:loading wire:target="saveInfo">Saving…</span>
            </button>
        </div>
    </div>

    {{-- Shop Info --}}
    @if ($user->shop)
        <div class="card p-5">
            <h3 class="font-semibold text-gray-900 text-sm mb-3">Shop Information</h3>
            <div class="space-y-2 text-sm">
                @foreach ([['label' => 'Shop Name', 'value' => $user->shop->name], ['label' => 'Email', 'value' => $user->shop->email], ['label' => 'Phone', 'value' => $user->shop->phone], ['label' => 'Address', 'value' => $user->shop->address], ['label' => 'Currency', 'value' => $user->shop->currency], ['label' => 'Timezone', 'value' => $user->shop->timezone], ['label' => 'Status', 'value' => ucfirst($user->shop->status->value)], ['label' => 'Member Since', 'value' => $user->shop->created_at->format('d M Y')]] as $row)
                    @if ($row['value'])
                        <div class="flex gap-3">
                            <span class="text-gray-400 w-32 shrink-0">{{ $row['label'] }}</span>
                            <span class="text-gray-800 font-medium">{{ $row['value'] }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
            @can('settings.manage')
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <a href="{{ route('settings') }}" wire:navigate
                        class="text-sm text-indigo-600 hover:underline font-medium">
                        Edit Shop Settings →
                    </a>
                </div>
            @endcan
        </div>
    @endif

    {{-- Login History --}}
    <div class="card p-5">
        <h3 class="font-semibold text-gray-900 text-sm mb-3">Account Security</h3>
        <div class="space-y-2 text-sm">
            <div class="flex gap-3">
                <span class="text-gray-400 w-32 shrink-0">Last Login</span>
                <span class="text-gray-800">
                    {{ $user->last_login_at?->format('d M Y H:i') ?? 'N/A' }}
                </span>
            </div>
            <div class="flex gap-3">
                <span class="text-gray-400 w-32 shrink-0">Password Set</span>
                <span class="text-gray-800">
                    {{ $user->password_changed_at?->format('d M Y') ?? 'Not set yet' }}
                </span>
            </div>
        </div>

        {{-- Change Password --}}
        <div class="mt-4 pt-4 border-t border-gray-100">
            @if (!$showPasswordForm)
                <button wire:click="$set('showPasswordForm', true)"
                    class="text-sm text-indigo-600 hover:underline font-medium">
                    Change Password
                </button>
            @else
                <div class="space-y-3">
                    <div>
                        <label class="label text-xs">Current Password *</label>
                        <input wire:model="currentPassword" type="password"
                            class="input @error('currentPassword') input-error @enderror">
                        @error('currentPassword')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">New Password *</label>
                        <input wire:model="newPassword" type="password"
                            class="input @error('newPassword') input-error @enderror" placeholder="Min 8 characters">
                        @error('newPassword')
                            <p class="error">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label text-xs">Confirm New Password *</label>
                        <input wire:model="confirmPassword" type="password" class="input"
                            placeholder="Repeat new password">
                    </div>
                    <div class="flex gap-3">
                        <button wire:click="changePassword" class="btn-primary btn-sm" wire:loading.attr="disabled"
                            wire:target="changePassword">
                            <span wire:loading.remove wire:target="changePassword">Update Password</span>
                            <span wire:loading wire:target="changePassword">Updating…</span>
                        </button>
                        <button wire:click="$set('showPasswordForm', false)"
                            class="btn-secondary btn-sm">Cancel</button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Subscription shortcut --}}
    <div class="card p-4 bg-indigo-50 border-indigo-200 flex items-center justify-between">
        <div>
            <div class="text-sm font-semibold text-indigo-800">Subscription & Billing</div>
            <div class="text-xs text-indigo-500 mt-0.5">View your plan, usage limits, and invoice history.</div>
        </div>
        <a href="{{ route('settings.subscription') }}" wire:navigate
            class="btn-sm bg-indigo-600 text-white rounded-lg px-3 py-1.5 text-xs font-medium">
            View →
        </a>
    </div>

</div>
