<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e3a5f">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ auth()->user()?->shop?->name ?? 'ShopERP' }}">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192.png">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ auth()->user()?->shop?->name ?? 'ShopERP' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-gray-50 font-sans antialiased" x-data="{ sidebarOpen: false }">

    {{-- ── MOBILE OVERLAY ── --}}
    <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden" style="display:none;">
    </div>
    <div class="flex min-h-screen">
        {{-- ── SIDEBAR ── --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-white border-r border-gray-200
                  transform transition-transform duration-200 ease-in-out
                  lg:translate-x-0 lg:static lg:z-auto lg:flex">

            {{-- Brand --}}
            <div class="flex h-14 items-center justify-between px-4 border-b border-gray-100 shrink-0">
                <div class="min-w-0">
                    @if (auth()->user()?->shop?->logo_path)
                        <img src="{{ Storage::url(auth()->user()->shop->logo_path) }}" class="h-8 w-auto object-contain"
                            alt="logo">
                    @else
                        <div class="font-bold text-indigo-700 truncate text-sm">
                            {{ auth()->user()?->shop?->name ?? 'ShopERP' }}
                        </div>
                    @endif
                    <div class="text-xs text-gray-400 truncate">
                        {{ auth()->user()?->name }}
                    </div>
                </div>
                {{-- Close button (mobile only) --}}
                <button @click="sidebarOpen = false"
                    class="lg:hidden p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 overflow-y-auto py-2 px-2 space-y-0.5">
                @php
                    use Illuminate\Support\Facades\Route;
                    $user = auth()->user();

                    $navLinks = [
                        [
                            'route' => 'dashboard',
                            'label' => 'Dashboard',
                            'icon' =>
                                'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                        ],
                        [
                            'route' => 'pos',
                            'label' => 'POS',
                            'icon' =>
                                'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
                        ],
                        [
                            'route' => 'sales.index',
                            'label' => 'Sales',
                            'icon' =>
                                'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        ],
                        [
                            'route' => 'customers.index',
                            'label' => 'Customers',
                            'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                        ],
                        [
                            'route' => 'products.index',
                            'label' => 'Products',
                            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                        ],
                        [
                            'route' => 'purchases.index',
                            'label' => 'Purchases',
                            'icon' =>
                                'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
                        ],
                        [
                            'route' => 'suppliers.index',
                            'label' => 'Suppliers',
                            'icon' =>
                                'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                        ],
                        [
                            'route' => 'used-phones.index',
                            'label' => 'Used Phones',
                            'icon' =>
                                'M12 18h.01M8 21l4-4 4 4M3 4.5A2.5 2.5 0 015.5 2h13A2.5 2.5 0 0121 4.5v9.5a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 14V4.5z',
                        ],
                        [
                            'route' => 'finance-partners.index',
                            'label' => 'EMI Partners',
                            'icon' =>
                                'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 0V5',
                        ],
                        [
                            'route' => 'service.index',
                            'label' => 'Service',
                            'icon' =>
                                'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0',
                        ],
                        [
                            'route' => 'expenses.index',
                            'label' => 'Expenses',
                            'icon' =>
                                'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2',
                        ],
                        [
                            'route' => 'payroll.index',
                            'label' => 'Payroll',
                            'icon' =>
                                'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                        ],
                        [
                            'route' => 'employees.index',
                            'label' => 'Employees',
                            'icon' =>
                                'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                        ],
                        [
                            'route' => 'treasury.index',
                            'label' => 'Treasury',
                            'icon' =>
                                'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                        ],
                        ['route' => '', 'label' => 'REPORTS', 'icon' => ''],
                        [
                            'route' => 'reports.pl',
                            'label' => 'P&L',
                            'icon' =>
                                'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                        ],
                        [
                            'route' => 'reports.trial-balance',
                            'label' => 'Trial Balance',
                            'icon' =>
                                'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
                        ],
                        [
                            'route' => 'reports.balance-sheet',
                            'label' => 'Balance Sheet',
                            'icon' =>
                                'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                        ],
                        [
                            'route' => 'reports.general-ledger',
                            'label' => 'Ledger',
                            'icon' =>
                                'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                        ],
                        [
                            'route' => 'reports.sales',
                            'label' => 'Sales Rpt',
                            'icon' =>
                                'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                        ],
                        [
                            'route' => 'reports.stock',
                            'label' => 'Stock',
                            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                        ],
                        [
                            'route' => 'reports.customer-due',
                            'label' => 'Due Rpt',
                            'icon' =>
                                'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7',
                        ],
                        [
                            'route' => 'reports.account-statement',
                            'label' => 'Acc Stmt',
                            'icon' =>
                                'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                        ],
                        [
                            'route' => 'reports.cash-flow',
                            'label' => 'Cash Flow',
                            'icon' => 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4',
                        ],
                        [
                            'route' => 'reports.service',
                            'label' => 'Service Rpt',
                            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0',
                        ],
                        [
                            'route' => 'reports.imei-ledger',
                            'label' => 'IMEI Ledger',
                            'icon' => 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01',
                        ],
                        [
                            'route' => 'settings',
                            'label' => 'Settings',
                            'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0 M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                        ],
                    ];
                @endphp

                @foreach ($navLinks as $link)
                    @if ($link['route'] === '')
                        <div class="px-3 pt-4 pb-1">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">
                                {{ $link['label'] }}
                            </span>
                        </div>
                    @else
                        @php $active = request()->routeIs($link['route']) || request()->routeIs($link['route'].'.*'); @endphp
                        <a href="{{ route($link['route']) }}" wire:navigate @click="sidebarOpen = false"
                            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-colors
                              {{ $active ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                            <svg class="w-4 h-4 shrink-0 {{ $active ? 'text-indigo-600' : 'text-gray-400' }}"
                                fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $link['icon'] }}" />
                            </svg>
                            <span class="truncate">{{ $link['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            </nav>

            {{-- Logout --}}
            <div class="p-3 border-t border-gray-100 shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium
                           text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors">
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

        {{-- ── MAIN AREA ── --}}
        <div class="flex flex-col lg:flex-row min-h-screen">
            {{-- Sidebar space on desktop --}}
            {{-- <div class="hidden lg:block w-64 shrink-0"></div> --}}

            {{-- Content wrapper --}}
            <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

                {{-- ── TOP BAR ── --}}
                <header
                    class="sticky top-0 z-30 bg-white border-b border-gray-200 h-14 flex items-center px-4 gap-3 shrink-0">
                    {{-- Hamburger (mobile) --}}
                    <button @click="sidebarOpen = true"
                        class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    {{-- Page title (dynamic) --}}
                    <div class="font-semibold text-gray-800 text-sm truncate flex-1">
                        @isset($title)
                            {{ $title }}
                        @endisset
                    </div>

                    {{-- Notification bell placeholder --}}
                    <div class="flex items-center gap-2">
                        @if (session('balance_warning'))
                            <div
                                class="text-xs text-amber-600 bg-amber-50 border border-amber-200
                                    rounded-lg px-3 py-1.5 max-w-xs truncate hidden sm:block">
                                ⚠ {{ session('balance_warning') }}
                            </div>
                        @endif

                        {{-- User avatar --}}
                        <div
                            class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center
                                text-white text-xs font-bold shrink-0">
                            {{ substr(auth()->user()?->name ?? 'U', 0, 1) }}
                        </div>
                    </div>
                </header>

                {{-- ── PAGE CONTENT ── --}}
                <main class="flex-1 p-4 sm:p-5 lg:p-6 overflow-x-hidden">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>

    {{-- ── TOAST NOTIFICATIONS ── --}}
    <div x-data="{
        notifications: [],
        add(e) {
            const n = { id: Date.now(), ...e.detail[0] };
            this.notifications.push(n);
            setTimeout(() => this.remove(n.id), 4000);
        },
        remove(id) { this.notifications = this.notifications.filter(n => n.id !== id); }
    }" @notify.window="add($event)"
        class="fixed bottom-4 right-4 z-[200] space-y-2 max-w-sm w-full px-4 sm:px-0">

        <template x-for="n in notifications" :key="n.id">
            <div x-show="true" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150" x-transition:leave-end="opacity-0"
                :class="{
                    'bg-green-600': n.type === 'success',
                    'bg-red-600': n.type === 'error',
                    'bg-amber-500': n.type === 'warning',
                    'bg-indigo-600': n.type === 'info' || !n.type,
                }"
                class="flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg text-white text-sm">
                <span x-text="n.message" class="flex-1 leading-snug"></span>
                <button @click="remove(n.id)" class="shrink-0 opacity-70 hover:opacity-100">✕</button>
            </div>
        </template>
    </div>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js');
        }
    </script>
    @livewireScripts
    <script>
        // PWA install handler
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', e => {
            e.preventDefault();
            deferredPrompt = e;
            document.getElementById('pwa-install-btn')?.classList.remove('hidden');
        });

        function installPwa() {
            deferredPrompt?.prompt();
            deferredPrompt?.userChoice.then(() => {
                deferredPrompt = null;
            });
        }
    </script>
</body>

</html>
