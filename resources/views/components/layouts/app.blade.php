<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        @isset($title)
            {{ $title }} —
        @endisset ShopSaaS
    </title>
    <meta name="theme-color" content="#4f46e5">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/images/icon-192.png">
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
    @notify.window="
        const d = $event.detail;
        const n = Array.isArray(d) ? d[0] : d;
        addNotification(n?.type ?? 'info', n?.message ?? '');
    "
    {{-- Impersonation Banner --}} @if (session('impersonator_id'))
    <div
        class="bg-amber-500 text-amber-950 text-sm font-semibold text-center py-2 px-4 flex items-center justify-center gap-4">
        <span>⚠️ You are impersonating {{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('impersonation.stop') }}" class="inline">
            @csrf
            <button type="submit" class="underline hover:no-underline">Return to Admin</button>
        </form>
    </div>
    @endif

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

    <div class="min-h-full flex pb-16 lg:pb-0">
        {{-- Desktop Sidebar --}}
        <aside class="hidden lg:flex flex-col w-64 bg-white border-r border-gray-200 fixed inset-y-0 z-30">
            <div class="flex items-center h-16 px-6 border-b border-gray-100">
                <div>
                    <span class="font-bold text-indigo-700 text-lg">ShopSaaS</span>
                    <div class="text-xs text-gray-400 truncate max-w-[180px]">{{ auth()->user()?->shop?->name }}</div>
                </div>
            </div>
            <nav class="flex-1 p-3 space-y-0.5 overflow-y-auto">
                @php
                    $navLinks = [
                        [
                            'route' => 'dashboard',
                            'label' => 'Dashboard',
                            'd' =>
                                'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                        ],
                        [
                            'route' => 'products.index',
                            'label' => 'Products',
                            'd' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                        ],
                        [
                            'route' => 'suppliers.index',
                            'label' => 'Suppliers',
                            'd' =>
                                'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                        ],
                        [
                            'route' => 'purchases.index',
                            'label' => 'Purchases',
                            'd' =>
                                'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
                        ],
                        [
                            'route' => 'customers.index',
                            'label' => 'Customers',
                            'd' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        ],
                        [
                            'route' => 'pos',
                            'label' => 'POS',
                            'd' =>
                                'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
                        ],
                        [
                            'route' => 'sales.index',
                            'label' => 'Sales',
                            'd' =>
                                'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        ],
                        [
                            'route' => 'service.index',
                            'label' => 'Service',
                            'd' =>
                                'M12 18h.01M8 21l4-4 4 4M3 4.5A2.5 2.5 0 015.5 2h13A2.5 2.5 0 0121 4.5v9.5a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 14V4.5z',
                        ],
                        [
                            'route' => 'finance-partners.index',
                            'label' => 'EMI Partners',
                            'd' =>
                                'M2.25 8.25h19.5m-18 4.5h16.5m-15 4.5h13.5M3 4.5h18A1.5 1.5 0 0122.5 6v12A1.5 1.5 0 0121 19.5H3A1.5 1.5 0 011.5 18V6A1.5 1.5 0 013 4.5z',
                        ],
                        [
                            'route' => 'used-phones.index',
                            'label' => 'Used Phones',
                            'd' =>
                                'M12 18h.01M8 21l4-4 4 4M3 4.5A2.5 2.5 0 015.5 2h13A2.5 2.5 0 0121 4.5v9.5a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 14V4.5z',
                        ],
                        [
                            'route' => 'expenses.index',
                            'label' => 'Expenses',
                            'd' =>
                                'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z',
                        ],
                        [
                            'route' => 'payroll.index',
                            'label' => 'Payroll',
                            'd' =>
                                'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                        ],
                        [
                            'route' => 'employees.index',
                            'label' => 'Employees',
                            'd' =>
                                'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                        ],
                        // Reports section separator group
                        [
                            'route' => 'reports.pl',
                            'label' => 'P&L Report',
                            'd' =>
                                'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                        ],
                        [
                            'route' => 'reports.sales',
                            'label' => 'Sales Report',
                            'd' =>
                                'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                        ],
                        [
                            'route' => 'reports.stock',
                            'label' => 'Stock Report',
                            'd' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                        ],
                        [
                            'route' => 'reports.customer-due',
                            'label' => 'Due Report',
                            'd' =>
                                'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 0V5m0 2a2 2 0 01-2 2m2-2a2 2 0 012 2m-2 4a2 2 0 01-2-2m2 2a2 2 0 012-2m-6 4a9 9 0 1118 0A9 9 0 010 12z',
                        ],
                        [
                            'route' => 'settings',
                            'label' => 'Settings',
                            'd' =>
                                'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                        ],
                    ];
                @endphp
                @foreach ($navLinks as $link)
                    <a href="{{ route($link['route']) }}" wire:navigate
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                            {{ request()->routeIs($link['route'] . '*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['d'] }}" />
                        </svg>
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>
            <div class="p-4 border-t border-gray-100">
                <div class="text-xs text-gray-400 mb-1">{{ auth()->user()?->name }}</div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-red-600 transition-colors">Sign
                        out</button>
                </form>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="lg:pl-64 flex-1 flex flex-col min-h-screen">
            <header
                class="sticky top-0 z-20 bg-white border-b border-gray-200 h-14 flex items-center px-4 lg:px-8 gap-3">
                <button class="lg:hidden text-gray-500" @click="sidebarOpen = !sidebarOpen">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="text-sm font-semibold text-gray-900 flex-1">{{ $title ?? 'Dashboard' }}</h1>
            </header>
            <main class="flex-1 p-4 lg:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    {{-- Mobile Bottom Nav (PWA) --}}
    <nav class="lg:hidden fixed bottom-0 inset-x-0 bg-white border-t border-gray-200 z-30 flex">
        @php
            $bottomNav = [
                [
                    'route' => 'dashboard',
                    'label' => 'Home',
                    'd' =>
                        'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                ],
                [
                    'route' => 'products.index',
                    'label' => 'Products',
                    'd' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                ],
                [
                    'route' => 'purchases.index',
                    'label' => 'Purchases',
                    'd' =>
                        'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
                ],
                [
                    'route' => 'suppliers.index',
                    'label' => 'Suppliers',
                    'd' =>
                        'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                ],
                [
                    'route' => 'settings',
                    'label' => 'Settings',
                    'd' =>
                        'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                ],
            ];
        @endphp
        @foreach ($bottomNav as $item)
            <a href="{{ route($item['route']) }}" wire:navigate
                class="flex-1 flex flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium transition-colors
                    {{ request()->routeIs($item['route'] . '*') ? 'text-indigo-600' : 'text-gray-500' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['d'] }}" />
                </svg>
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    @livewireScripts
</body>

</html>
