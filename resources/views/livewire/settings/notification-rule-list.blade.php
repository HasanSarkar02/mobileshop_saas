<div>
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Notification Rules</h1>
        <a href="{{ route('settings.notification-rules.create') }}" wire:navigate class="btn-primary btn-sm">New Rule</a>
    </div>

    <div class="card divide-y">
        @forelse($rules as $rule)
            <div class="flex items-center justify-between px-4 py-3">
                <div>
                    <p class="font-medium">{{ $rule->name }}</p>
                    <p class="text-xs text-gray-500">{{ $rule->event_type->label() }} —
                        {{ count($rule->conditions ?? []) }} condition(s)</p>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="badge {{ $rule->is_active ? 'badge-green' : 'badge-gray' }}">{{ $rule->is_active ? 'Active' : 'Inactive' }}</span>
                    <button wire:click="toggleActive({{ $rule->id }})"
                        class="btn-secondary btn-sm">{{ $rule->is_active ? 'Disable' : 'Enable' }}</button>
                    <a href="{{ route('settings.notification-rules.edit', $rule) }}" wire:navigate
                        class="btn-secondary btn-sm">Edit</a>
                    <button wire:click="delete({{ $rule->id }})" class="btn-danger btn-sm">Delete</button>
                </div>
            </div>
        @empty
            <p class="px-4 py-10 text-center text-gray-500">No rules yet — defaults from each event type apply.</p>
        @endforelse
    </div>
</div>
