<div class="max-w-2xl">
    <h1 class="text-xl font-semibold mb-4">Email (SMTP) Settings</h1>

    <div class="card p-6 space-y-4">
        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="smtp_enabled">
            <span>Enable email notifications for this shop</span>
        </label>

        <div>
            <label class="label">SMTP Host</label>
            <input type="text" wire:model="smtp_host" class="input w-full" placeholder="smtp.mailtrap.io">
            @error('smtp_host')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="label">Port</label>
                <input type="number" wire:model="smtp_port" class="input w-full" placeholder="587">
            </div>
            <div>
                <label class="label">Encryption</label>
                <select wire:model="smtp_encryption" class="input w-full">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                </select>
            </div>
        </div>

        <div>
            <label class="label">Username</label>
            <input type="text" wire:model="smtp_username" class="input w-full">
        </div>

        <div>
            <label class="label">Password</label>
            <input type="password" wire:model="smtp_password" class="input w-full"
                placeholder="Leave blank to keep current password">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="label">From Address</label>
                <input type="email" wire:model="smtp_from_address" class="input w-full">
                @error('smtp_from_address')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="label">From Name</label>
                <input type="text" wire:model="smtp_from_name" class="input w-full">
            </div>
        </div>

        <button wire:click="save" class="btn-primary">Save SMTP Settings</button>
    </div>

    <div class="card p-6 mt-6">
        <h2 class="font-semibold mb-2">Send Test Email</h2>
        <div class="flex gap-2">
            <input type="email" wire:model="testEmailAddress" class="input flex-1" placeholder="you@example.com">
            <button wire:click="sendTestEmail" class="btn-secondary">Send Test</button>
        </div>
        @error('testEmailAddress')
            <p class="error">{{ $message }}</p>
        @enderror
    </div>
</div>
