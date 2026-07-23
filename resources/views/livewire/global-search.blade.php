<div x-data="{
    open: @entangle('open'),
    init() {
        // Ctrl+K / Cmd+K
        window.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                this.open = true;
                $nextTick(() => this.$refs.searchInput?.focus());
            }
            if (e.key === 'Escape') this.close();
        });
    },
    close() {
        this.open = false;
        $wire.close();
    }
}">
    {{-- Trigger button in topbar --}}
    <button @click="open = true; $nextTick(() => $refs.searchInput?.focus())"
        class="hidden sm:flex items-center gap-2 px-3 py-1.5 text-sm text-gray-400
               bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors border border-gray-200">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <span>Search…</span>
        <kbd class="ml-2 text-xs bg-white border border-gray-200 rounded px-1.5 py-0.5 font-mono text-gray-400">
            Ctrl K
        </kbd>
    </button>

    {{-- Mobile search icon --}}
    <button @click="open = true; $nextTick(() => $refs.searchInput?.focus())"
        class="sm:hidden p-2 text-gray-500 hover:bg-gray-100 rounded-lg">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
    </button>

    {{-- Modal --}}
    <div x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-[100] flex items-start justify-center pt-4 sm:pt-[10vh] px-3 sm:px-4"
        style="display:none">

        {{-- Backdrop --}}
        <div @click="close()" class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

        {{-- Panel --}}
        <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl overflow-hidden z-10"
            @keydown.arrow-down.prevent="$wire.moveCursor(1)" @keydown.arrow-up.prevent="$wire.moveCursor(-1)"
            @keydown.enter.prevent="$wire.goToCursor()">

            {{-- Input --}}
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
                <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>

                <input x-ref="searchInput" wire:model.live.debounce.250ms="query" type="text"
                    placeholder="Search customers, products, IMEIs, invoices…"
                    class="flex-1 text-sm text-gray-900 placeholder-gray-400 bg-transparent focus:outline-none"
                    autocomplete="off" spellcheck="false">

                <div wire:loading wire:target="query">
                    <svg class="animate-spin w-4 h-4 text-indigo-500" fill="none" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"
                            class="opacity-25" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                    </svg>
                </div>

                {{-- Desktop Esc badge --}}
                <kbd
                    class="hidden sm:inline-block text-xs bg-gray-100 border border-gray-200 rounded px-1.5 py-0.5 font-mono text-gray-400">
                    Esc
                </kbd>

                {{-- Close Button (Mobile & Desktop) --}}
                <button type="button" @click="close()"
                    class="p-1 -mr-1 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Results --}}
            @if (strlen(trim($query)) >= 2)
                @php
                    $allResults = $this->results;
                    $flat = $this->flatResults;
                    $globalIdx = 0;
                @endphp

                @if (empty($allResults))
                    <div class="px-4 py-10 text-center text-gray-400 text-sm">
                        No results for "<strong>{{ $query }}</strong>"
                    </div>
                @else
                    <div class="max-h-[60vh] overflow-y-auto divide-y divide-gray-50">
                        @foreach ($allResults as $group => $items)
                            <div class="py-2">
                                <div class="px-4 py-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    {{ $group }}
                                </div>
                                @foreach ($items as $item)
                                    @php $isCursor = ($globalIdx === $cursor); @endphp
                                    <a href="{{ $item['url'] }}" wire:navigate @click="close()"
                                        class="flex items-center gap-3 px-4 py-2.5 transition-colors
                                              {{ $isCursor ? 'bg-indigo-50' : 'hover:bg-gray-50' }}">

                                        <span class="text-lg shrink-0 w-6 text-center">{{ $item['icon'] }}</span>

                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-medium text-gray-900 truncate">
                                                {{ $item['label'] }}
                                            </div>
                                            @if ($item['sub'])
                                                <div class="text-xs text-gray-400 truncate">{{ $item['sub'] }}</div>
                                            @endif
                                        </div>

                                        @if ($item['badge'])
                                            <span
                                                class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5
                                                         rounded-full shrink-0 font-medium">
                                                {{ $item['badge'] }}
                                            </span>
                                        @endif

                                        @if ($isCursor)
                                            <kbd
                                                class="text-xs bg-indigo-100 text-indigo-600 rounded
                                                        px-1.5 py-0.5 shrink-0">↵</kbd>
                                        @endif
                                    </a>
                                    @php $globalIdx++; @endphp
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Footer --}}
                <div
                    class="px-4 py-2 bg-gray-50 border-t border-gray-100
                            flex items-center gap-4 text-xs text-gray-400">
                    <span class="hidden sm:inline">↑↓ Navigate</span>
                    <span class="hidden sm:inline">↵ Open</span>
                    <span class="hidden sm:inline">Esc Close</span>
                    <span class="sm:ml-auto">
                        {{ collect($allResults)->flatten(1)->count() }} result(s)
                    </span>
                </div>
            @else
                {{-- Empty state / hints --}}
                <div class="px-4 py-6 space-y-2">
                    <div class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                        Quick access
                    </div>
                    @foreach ([['icon' => '🛒', 'label' => 'New Sale', 'url' => route('pos')], ['icon' => '👤', 'label' => 'Add Customer', 'url' => route('customers.create')], ['icon' => '📦', 'label' => 'Add Product', 'url' => route('products.create')], ['icon' => '🛍', 'label' => 'New Purchase', 'url' => route('purchases.create')]] as $shortcut)
                        <a href="{{ $shortcut['url'] }}" wire:navigate @click="close()"
                            class="flex items-center gap-3 px-3 py-2 rounded-lg
                                  text-sm text-gray-600 hover:bg-gray-50 transition-colors">
                            <span class="text-base">{{ $shortcut['icon'] }}</span>
                            {{ $shortcut['label'] }}
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
