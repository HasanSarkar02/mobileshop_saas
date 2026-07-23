@php $platformSettings = \App\Models\PlatformSetting::current(); @endphp
<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ $platformSettings->app_name }}</title>
    @if ($platformSettings->favicon_path)
        <link rel="icon" href="{{ \Illuminate\Support\Facades\Storage::url($platformSettings->favicon_path) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="h-full bg-gray-100 font-sans antialiased" x-data="{ sidebarOpen: false }">

    {{-- Mobile overlay --}}
    <div x-show="sidebarOpen" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-gray-900/60 lg:hidden" style="display:none;"></div>

    {{-- Main Layout Wrapper --}}
    <div class="flex h-full overflow-hidden">

        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 text-white flex flex-col shrink-0
                   transform transition-transform duration-200 lg:translate-x-0 lg:static lg:z-auto">

            {{-- Brand --}}
            <div class="h-14 flex items-center gap-3 px-4 border-b border-gray-700 shrink-0">
                @if ($platformSettings->logo_path)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($platformSettings->logo_path) }}"
                        alt="{{ $platformSettings->app_name }}" class="w-8 h-8 rounded-lg object-cover shrink-0">
                @else
                    <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center shrink-0">
                        <span
                            class="text-white font-bold text-sm">{{ strtoupper(substr($platformSettings->app_name, 0, 2)) }}</span>
                    </div>
                @endif
                <div class="min-w-0">
                    <div class="font-bold text-white text-sm truncate">{{ $platformSettings->app_name }}</div>
                    <div class="text-xs text-gray-400 truncate">Super Admin Panel</div>
                </div>
                <button @click="sidebarOpen = false" class="ml-auto lg:hidden text-gray-400 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
                @php
                    $adminLinks = [
                        [
                            'route' => 'admin.dashboard',
                            'label' => 'Dashboard',
                            'icon' =>
                                'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                        ],
                        [
                            'route' => 'admin.shops',
                            'label' => 'Shops',
                            'icon' =>
                                'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                        ],
                        [
                            'route' => 'admin.billing',
                            'label' => 'Billing',
                            'icon' =>
                                'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                        ],
                        [
                            'route' => 'admin.plans',
                            'label' => 'Plans',
                            'icon' =>
                                'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                        ],
                        [
                            'route' => 'admin.invoices',
                            'label' => 'Invoices',
                            'icon' =>
                                'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                        ],
                        [
                            'route' => 'admin.announcements',
                            'label' => 'Announcements',
                            'icon' =>
                                'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
                        ],
                        [
                            'route' => 'admin.impersonation-logs',
                            'label' => 'Impersonation Logs',
                            'icon' =>
                                'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                        ],
                        [
                            'route' => 'admin.settings',
                            'label' => 'Platform Settings',
                            'icon' =>
                                'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065zM15 12a3 3 0 11-6 0 3 3 0 016 0z',
                        ],
                    ];
                @endphp

                @foreach ($adminLinks as $link)
                    @php $active = request()->routeIs($link['route'].'*'); @endphp
                    <a href="{{ route($link['route']) }}" wire:navigate @click="sidebarOpen = false"
                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors
                               {{ $active ? 'bg-indigo-600 text-white' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}" />
                        </svg>
                        <span class="truncate">{{ $link['label'] }}</span>
                    </a>
                @endforeach

                <div class="border-t border-gray-700 my-2"></div>

                <a href="{{ route('admin.shops') }}" wire:navigate
                    class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-400
                           hover:bg-gray-800 hover:text-white transition-colors">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z" />
                    </svg>
                    <span>Impersonate Shop</span>
                </a>
            </nav>

            {{-- Admin logout --}}
            <div class="p-3 border-t border-gray-700 shrink-0">
                <div class="text-xs text-gray-500 mb-2 px-1">
                    Logged in as: {{ auth('admin')->user()?->name }}
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium
                               text-gray-400 hover:bg-red-900/40 hover:text-red-300 transition-colors">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main Content Area --}}
        <div class="flex flex-col flex-1 min-w-0">

            {{-- Topbar --}}
            <header class="bg-white border-b border-gray-200 h-14 flex items-center px-4 gap-3 shrink-0">
                <button @click="sidebarOpen = true" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="font-semibold text-gray-800 text-sm flex-1 truncate">
                    @isset($title)
                        {{ $title }}
                    @endisset
                </div>
                <span class="text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded-lg font-mono">
                    Super Admin
                </span>
            </header>

            {{-- Content --}}
            <main class="flex-1 overflow-y-auto p-4 sm:p-5 lg:p-6 bg-gray-100">
                <x-announcement-banner audience="admin_panel" />
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Toasts --}}
    <div x-data="{
        notifications: [],
        add(e) {
            const n = { id: Date.now(), ...e.detail[0] };
            this.notifications.push(n);
            setTimeout(() => this.remove(n.id), 4000);
        },
        remove(id) { this.notifications = this.notifications.filter(n => n.id !== id); }
    }" @notify.window="add($event)"
        class="fixed bottom-4 right-4 z-[200] space-y-2 max-w-sm w-full px-4 sm:px-0 pointer-events-none">
        <template x-for="n in notifications" :key="n.id">
            <div x-show="true" x-transition:enter-start="opacity-0 translate-y-2"
                x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave-end="opacity-0"
                :class="{
                    'bg-green-600': n.type === 'success',
                    'bg-red-600': n.type === 'error',
                    'bg-amber-500': n.type === 'warning',
                    'bg-indigo-600': !n.type || n.type === 'info',
                }"
                class="flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg text-white text-sm
                        transition-all duration-200 pointer-events-auto">
                <span x-text="n.message" class="flex-1 leading-snug"></span>
                <button @click="remove(n.id)"
                    class="shrink-0 opacity-70 hover:opacity-100 text-lg leading-none">✕</button>
            </div>
        </template>
    </div>

    @livewireScripts
</body>

</html>
