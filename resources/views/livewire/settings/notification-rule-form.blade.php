<div class="max-w-3xl card p-6 space-y-6">
    <h1 class="text-lg font-semibold">Notification Rule</h1>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="label">Name</label>
            <input type="text" wire:model="name" class="input w-full">
            @error('name')
                <p class="error">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="label">Event Type</label>
            <select wire:model="event_type" class="input w-full">
                <option value="">Select…</option>
                @foreach ($eventTypes as $et)
                    <option value="{{ $et->value }}">{{ $et->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between mb-2">
            <label class="label">Conditions (all must match)</label>
            <button wire:click="addCondition" class="btn-secondary btn-sm">Add Condition</button>
        </div>
        @foreach ($conditions as $i => $condition)
            <div class="flex gap-2 mb-2">
                <input type="text" wire:model="conditions.{{ $i }}.field" placeholder="field e.g. amount"
                    class="input flex-1">
                <select wire:model="conditions.{{ $i }}.operator" class="input w-28">
                    <option value=">">&gt;</option>
                    <option value=">=">&gt;=</option>
                    <option value="<">&lt;</option>
                    <option value="<=">&lt;=</option>
                    <option value="==">==</option>
                    <option value="!=">!=</option>
                </select>
                <input type="number" step="0.01" wire:model="conditions.{{ $i }}.value"
                    placeholder="value" class="input w-32">
                <button wire:click="removeCondition({{ $i }})" class="btn-danger btn-sm">✕</button>
            </div>
        @endforeach
        <p class="text-xs text-gray-500">No conditions = rule always applies to this event type.</p>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="label">Channel Override (blank = use event default)</label>
            @foreach ($channels as $c)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="channel_override" value="{{ $c->value }}">
                    {{ $c->label() }}
                </label>
            @endforeach
        </div>
        <div>
            <label class="label">Priority Override</label>
            <select wire:model="priority_override" class="input w-full">
                <option value="">Use event default</option>
                @foreach ($priorities as $p)
                    <option value="{{ $p->value }}">{{ $p->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div>
        <label class="label">Recipient Override</label>
        <select wire:model.live="recipient_override_type" class="input w-full mb-2">
            <option value="">Use default recipients</option>
            <option value="permission">By permission</option>
            <option value="users">Specific users</option>
        </select>

        @if ($recipient_override_type === 'permission')
            <select wire:model="recipient_override_permission" class="input w-full">
                <option value="">Select permission…</option>
                @foreach ($permissions as $perm)
                    <option value="{{ $perm->value }}">{{ $perm->label() }}</option>
                @endforeach
            </select>
        @elseif($recipient_override_type === 'users')
            @foreach ($users as $user)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="recipient_override_user_ids" value="{{ $user->id }}">
                    {{ $user->name }}
                </label>
            @endforeach
        @endif
    </div>

    <label class="flex items-center gap-2">
        <input type="checkbox" wire:model="is_active">
        <span>Active</span>
    </label>

    <button wire:click="save" class="btn-primary">Save Rule</button>
</div>
