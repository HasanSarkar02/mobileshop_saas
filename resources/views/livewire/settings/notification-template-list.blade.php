<div>
    <h1 class="text-xl font-semibold mb-4">Notification Templates</h1>

    <div class="card divide-y">
        @foreach ($rows as $row)
            <div class="flex items-center justify-between px-4 py-3">
                <div>
                    <p class="font-medium">{{ $row['event_type']->label() }}</p>
                    <p class="text-xs text-gray-500">{{ $row['channel']->label() }}
                        @if ($row['override'])
                            <span class="badge badge-blue ml-1">Custom</span>
                        @else
                            <span class="badge badge-gray ml-1">System Default</span>
                        @endif
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('settings.notification-templates.edit', ['eventType' => $row['event_type']->value, 'channel' => $row['channel']->value]) }}"
                        wire:navigate class="btn-secondary btn-sm">
                        {{ $row['override'] ? 'Edit override' : 'Create override' }}
                    </a>
                    @if ($row['override'])
                        <button wire:click="resetToSystemDefault({{ $row['override']->id }})"
                            class="btn-danger btn-sm">Reset</button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
