<div class="max-w-2xl space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $shop->name }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">Manage which modules this shop can access.</p>
        </div>
        <a href="{{ route('admin.shops.show', $shop) }}" wire:navigate class="btn-secondary btn-sm">← Back</a>
    </div>

    <div class="card p-6 space-y-5">

        {{-- All enabled toggle --}}
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div>
                <div class="font-semibold text-gray-900 text-sm">Unrestricted Access</div>
                <div class="text-xs text-gray-400 mt-0.5">Enable all features. Ignores individual selections below.</div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input wire:model.live="allEnabled" type="checkbox" class="sr-only peer" @change="$wire.toggleAll()">
                <div
                    class="w-10 h-6 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600
                    peer-checked:after:translate-x-4 after:content-[''] after:absolute
                    after:top-0.5 after:left-0.5 after:bg-white after:rounded-full
                    after:h-5 after:w-5 after:transition-transform">
                </div>
            </label>
        </div>

        {{-- Feature checkboxes --}}
        <div class="{{ $allEnabled ? 'opacity-40 pointer-events-none' : '' }}">
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Individual Features</div>
            <div class="grid grid-cols-2 gap-2">
                @foreach ($this->allFeatures() as $feature)
                    <label
                        class="flex items-center gap-3 p-3 rounded-xl border border-gray-100
                                  hover:bg-gray-50 cursor-pointer transition-colors
                                  {{ in_array($feature->value, $selectedFeatures) ? 'bg-indigo-50 border-indigo-200' : '' }}">
                        <input type="checkbox" wire:model="selectedFeatures" value="{{ $feature->value }}"
                            class="rounded border-gray-300 text-indigo-600">
                        <span class="text-sm font-medium text-gray-700">
                            {{ $feature->icon() }} {{ $feature->label() }}
                        </span>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex gap-3 pt-2 border-t border-gray-100">
            <button wire:click="save" class="btn-primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove>Save Feature Configuration</span>
                <span wire:loading>Saving…</span>
            </button>
        </div>
    </div>
</div>
