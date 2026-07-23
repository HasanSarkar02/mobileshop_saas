<div class="max-w-3xl space-y-6">
    <h2 class="text-xl font-bold text-gray-900">Platform Settings</h2>

    {{-- Branding --}}
    <div class="card p-6 space-y-4">
        <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">Branding</h3>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label text-xs">Application Name *</label>
                <input wire:model="appName" type="text" class="input @error('appName') input-error @enderror">
                @error('appName')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label text-xs">Logo</label>
                <div class="flex items-center gap-3">
                    @if ($logoUpload)
                        <img src="{{ $logoUpload->temporaryUrl() }}" class="h-10 w-10 rounded-lg object-cover border">
                    @elseif ($currentLogoPath)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($currentLogoPath) }}"
                            class="h-10 w-10 rounded-lg object-cover border">
                    @else
                        <div
                            class="h-10 w-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-400 text-xs">
                            None</div>
                    @endif
                    <input wire:model="logoUpload" type="file" accept="image/*" class="text-xs">
                </div>
                @error('logoUpload')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label text-xs">Favicon</label>
                <div class="flex items-center gap-3">
                    @if ($faviconUpload)
                        <img src="{{ $faviconUpload->temporaryUrl() }}" class="h-8 w-8 rounded object-cover border">
                    @elseif ($currentFaviconPath)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($currentFaviconPath) }}"
                            class="h-8 w-8 rounded object-cover border">
                    @else
                        <div
                            class="h-8 w-8 rounded bg-indigo-100 flex items-center justify-center text-indigo-400 text-xs">
                            —</div>
                    @endif
                    <input wire:model="faviconUpload" type="file" accept="image/*" class="text-xs">
                </div>
                @error('faviconUpload')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <p class="text-xs text-gray-400">
            Uploads need <code>php artisan storage:link</code> to have been run once on the server.
        </p>
    </div>

    {{-- Support --}}
    <div class="card p-6 space-y-4">
        <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">Support Contact</h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label text-xs">Support Email</label>
                <input wire:model="supportEmail" type="email"
                    class="input @error('supportEmail') input-error @enderror">
                @error('supportEmail')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label text-xs">Support Phone</label>
                <input wire:model="supportPhone" type="text"
                    class="input @error('supportPhone') input-error @enderror">
                @error('supportPhone')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    {{-- Defaults --}}
    <div class="card p-6 space-y-4">
        <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">New Shop Defaults</h3>
        <div class="grid sm:grid-cols-3 gap-4">
            <div>
                <label class="label text-xs">Default Currency</label>
                <input wire:model="defaultCurrency" type="text" maxlength="3"
                    class="input uppercase @error('defaultCurrency') input-error @enderror">
                @error('defaultCurrency')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label text-xs">Default Timezone</label>
                <input wire:model="defaultTimezone" type="text"
                    class="input @error('defaultTimezone') input-error @enderror">
                @error('defaultTimezone')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label text-xs">Default Trial Days</label>
                <input wire:model="defaultTrialDays" type="number" min="1" max="90"
                    class="input @error('defaultTrialDays') input-error @enderror">
                @error('defaultTrialDays')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <p class="text-xs text-gray-400">Pre-fills the "Create Shop" form. Doesn't affect shops already created.</p>
    </div>

    {{-- Legal / Footer --}}
    <div class="card p-6 space-y-4">
        <h3 class="font-semibold text-gray-900 text-sm border-b border-gray-100 pb-2">Legal & Footer</h3>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label text-xs">Terms of Service URL</label>
                <input wire:model="termsUrl" type="text" placeholder="https://…"
                    class="input @error('termsUrl') input-error @enderror">
                @error('termsUrl')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label text-xs">Privacy Policy URL</label>
                <input wire:model="privacyUrl" type="text" placeholder="https://…"
                    class="input @error('privacyUrl') input-error @enderror">
                @error('privacyUrl')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div>
            <label class="label text-xs">Footer Text</label>
            <input wire:model="footerText" type="text" placeholder="© 2026 Your Company."
                class="input @error('footerText') input-error @enderror">
            @error('footerText')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="flex gap-3">
        <button wire:click="save" class="btn-primary" wire:loading.attr="disabled"
            wire:target="save,logoUpload,faviconUpload">
            <span wire:loading.remove wire:target="save">Save Settings</span>
            <span wire:loading wire:target="save">Saving…</span>
        </button>
    </div>

    {{-- Maintenance Mode --}}
    <div
        class="card p-6 space-y-4 border-2 {{ $this->isDownForMaintenance() ? 'border-red-200' : 'border-gray-100' }}">
        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
            <h3 class="font-semibold text-gray-900 text-sm">Maintenance Mode</h3>
            <span class="badge {{ $this->isDownForMaintenance() ? 'badge-red' : 'badge-green' }} text-xs">
                {{ $this->isDownForMaintenance() ? 'Down' : 'Live' }}
            </span>
        </div>

        <p class="text-sm text-gray-500">
            Puts the entire application into maintenance mode for all shops. Only super admins with the
            bypass link can access it while it's on.
        </p>

        @if ($maintenanceBypassUrl)
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-sm space-y-1">
                <div class="font-semibold text-amber-800">Bypass link (save this — it won't be shown again)</div>
                <div class="font-mono text-xs text-amber-700 break-all">{{ $maintenanceBypassUrl }}</div>
            </div>
        @endif

        <div class="flex gap-3">
            @if ($this->isDownForMaintenance())
                <button wire:click="disableMaintenance" wire:confirm="Bring the site back online?"
                    class="btn btn-sm btn-success">
                    Disable Maintenance Mode
                </button>
            @else
                <button wire:click="enableMaintenance"
                    wire:confirm="This takes the entire platform offline for all shops. Continue?"
                    class="btn btn-sm btn-danger">
                    Enable Maintenance Mode
                </button>
            @endif
        </div>

        <p class="text-xs text-gray-400">
            Running more than one app server? Set <code>APP_MAINTENANCE_DRIVER=cache</code> (with a shared
            cache store) in <code>.env</code> so every server sees the same maintenance state.
        </p>
    </div>
</div>
