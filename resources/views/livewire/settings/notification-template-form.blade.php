<div class="grid grid-cols-2 gap-6">
    <div class="card p-6 space-y-4">
        <h1 class="text-lg font-semibold">{{ $eventTypeLabel }} — {{ ucfirst($channel) }}</h1>

        @if ($channel === 'email')
            <div>
                <label class="label">Subject</label>
                <input type="text" wire:model.live.debounce.300ms="subject" class="input w-full">
            </div>
        @endif

        <div>
            <label class="label">Body</label>
            <textarea wire:model.live.debounce.300ms="body" rows="8" class="input w-full"></textarea>
        </div>

        <div>
            <p class="text-xs text-gray-500 mb-1">Available placeholders:</p>
            <div class="flex flex-wrap gap-1">
                @foreach ($placeholders as $p)
                    <code class="text-xs bg-gray-100 px-1.5 py-0.5 rounded">{{ '{{' . $p . ' ?>' }}' }}</code>
                @endforeach
            </div>
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="is_active">
            <span>Active</span>
        </label>

        <button wire:click="save" class="btn-primary">Save</button>
    </div>

    <div class="card p-6 bg-gray-50">
        <p class="text-xs uppercase text-gray-400 mb-2">Live Preview (sample data)</p>
        @if ($channel === 'email')
            <p class="font-medium mb-2">{{ $this->previewSubject }}</p>
        @endif
        <p class="text-sm whitespace-pre-line">{{ $this->previewBody }}</p>
    </div>
</div>
