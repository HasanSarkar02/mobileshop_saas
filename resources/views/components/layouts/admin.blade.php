<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @isset($title)
            {{ $title }} —
        @endisset Super Admin · ShopSaaS
    </title>
    <meta name="theme-color" content="#4338ca">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="h-full font-sans" x-data="{
    sidebarOpen: false,
    notifications: [],
    addNotification(type, message) {
        const n = { id: Date.now(), type, message };
        this.notifications.push(n);
        setTimeout(() => this.notifications = this.notifications.filter(x => x.id !== n.id), 4000);
    }
}"
    @notify.window="addNotification($event.detail[0]?.type ?? 'info', $event.detail[0]?.message ?? '')">

    {{-- Toast Notifications --}}
    <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 w-80 pointer-events-none" aria-live="polite">
        <template x-for="n in notifications" :key="n.id">
            <div class="pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg border text-sm font-medium"
                :class="{
                    'bg-green-50 border-green-300 text-green-800': n.type === 'success',
                    'bg-red-50 border-red-300 text-red-800': n.type === 'error',
                    'bg-yellow-50 border-yellow-300 text-yellow-800': n.type === 'warning',
                    'bg-blue-50 border-blue-300 text-blue-800': n.type === 'info'
                }"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-x-4"
                x-transition:enter-end="opacity-100 translate-x-0">
                <span x-text="n.message" class="flex-1"></span>
                <button @click="notifications = notifications.filter(x => x.id !== n.id)"
                    class="opacity-60 hover:opacity-100 shrink-0">✕</button>
            </div>
        </template>
    </div>

    <div class="min-h-full flex">
        {{-- Sidebar --}}
        <aside class="hidden lg:flex flex-col w-64 bg-indigo-950 fixed inset-y-0 z-30">
            <div class="flex items-center h-16 px-6 border-b border-indigo-900">
                <span class="text-white font-semibold text-lg tracking-tight">⚡ ShopSaaS</span>
            </div>
            <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
                @php
                    $navItems = [
                        [
                            'route' => 'admin.dashboard',
                            'label' => 'Dashboard',
                            'icon' =>
                                'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                        ],
                        [
                            'route' => 'admin.shops.index',
                            'label' => 'Shops',
                            'icon' =>
                                'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                        ],
                    ];
                @endphp
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}" wire:navigate
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                            {{ request()->routeIs($item['route'] . '*') ? 'bg-indigo-800 text-white' : 'text-indigo-300 hover:bg-indigo-900 hover:text-white' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                        </svg>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
            <div class="p-4 border-t border-indigo-900">
                <div class="text-xs text-indigo-400 mb-2">{{ auth('admin')->user()?->email }}</div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-2 text-indigo-400 hover:text-white text-sm transition-colors w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sign out
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main --}}
        <div class="lg:pl-64 flex-1 flex flex-col min-h-screen">
            <header class="sticky top-0 z-20 bg-white border-b border-gray-200 h-16 flex items-center px-4 lg:px-8">
                <button class="lg:hidden mr-3 text-gray-500" @click="sidebarOpen = true">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-base font-semibold text-gray-900 flex-1">{{ $title ?? 'Admin Panel' }}</h1>
                <span class="text-sm text-gray-500 hidden sm:block">{{ auth('admin')->user()?->name }}</span>
            </header>

            <main class="flex-1 p-4 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>

</html>
