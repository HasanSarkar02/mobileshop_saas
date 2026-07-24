<!DOCTYPE html>
<html lang="en" class="antialiased">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#4f46e5">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="{{ auth()->user()?->shop?->name ?? 'SmartShop ERP' }}">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ auth()->user()?->shop?->name ?? 'SmartShop ERP' }}</title>
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 min-h-screen font-sans"
    x-data="{ sidebarOpen: false }">

    <div class="min-h-screen flex">

        {{-- ── MOBILE OVERLAY ── --}}
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-gray-900/60 backdrop-blur-sm lg:hidden" style="display:none;"
            aria-hidden="true">
        </div>

        {{-- ── SIDEBAR ── --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700
                      transition-transform duration-200 ease-in-out
                      lg:translate-x-0 lg:static lg:sticky lg:top-0 lg:h-screen lg:shrink-0">

            {{-- Brand --}}
            @php
                $settings = \App\Models\PlatformSetting::current();
                $shop = auth()->user()?->shop;
            @endphp

            @php
                $settings = \App\Models\PlatformSetting::current();
                $shop = auth()->user()?->shop;
            @endphp

            <div
                class="relative flex h-16 items-center justify-center px-4 border-b border-gray-100 dark:border-gray-700 shrink-0">
                {{-- Logo Container (Centered & Larger) --}}
                <div class="flex items-center justify-center w-full">
                    @if ($shop?->logo_path)
                        <img src="{{ Storage::url($shop->logo_path) }}"
                            class="h-18 max-h-18 w-auto max-w-full object-contain py-1"
                            alt="{{ $shop->name ?? 'Shop Logo' }}">

                        {{-- ২. সিস্টেম লোগো --}}
                    @elseif ($settings?->logo_path)
                        <img src="{{ asset('storage/' . $settings->logo_path) }}"
                            class="h-18 max-h-18 w-auto max-w-full object-contain py-1"
                            alt="{{ $settings->app_name ?? 'System Logo' }}">
                    @else
                        <div class="font-bold text-indigo-700 dark:text-indigo-400 truncate text-base text-center">
                            {{ $settings?->app_name ?? ($shop?->name ?? 'SmartShop ERP') }}
                        </div>
                    @endif
                </div>

                {{-- Close button (mobile only) --}}
                <button @click="sidebarOpen = false"
                    class="lg:hidden absolute right-3 p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-gray-300 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Nav --}}
            <nav class="flex-1 overflow-y-auto py-3 px-3 space-y-1">
                @php
                    use Illuminate\Support\Facades\Route;
                    use App\Enums\PermissionEnum;

                    $features = app(\App\Services\ShopFeatureService::class);
                    $user = auth()->user();

                    // Check if user has access to any reports to conditionally show the divider
                    $canViewAnyReport =
                        $user->can(PermissionEnum::AccountingViewBasicReports->value) ||
                        $user->can(PermissionEnum::AccountingViewFullReports->value);

                    $navLinks = array_filter([
                        $user->can(PermissionEnum::DashboardView->value)
                            ? [
                                'route' => 'dashboard',
                                'label' => 'Dashboard',
                                'icon' =>
                                    'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                            ]
                            : null,
                        $features->enabled('pos') && $user->can(PermissionEnum::SalesCreate->value)
                            ? [
                                'route' => 'pos',
                                'label' => 'POS',
                                'icon' =>
                                    'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
                            ]
                            : null,
                        $features->enabled('sales') && $user->can(PermissionEnum::SalesView->value)
                            ? [
                                'route' => 'sales.index',
                                'label' => 'Sales',
                                'icon' =>
                                    'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                            ]
                            : null,
                        $features->enabled('customers') && $user->can(PermissionEnum::CustomersView->value)
                            ? [
                                'route' => 'customers.index',
                                'label' => 'Customers',
                                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                            ]
                            : null,
                        $features->enabled('inventory') && $user->can(PermissionEnum::ProductsView->value)
                            ? [
                                'route' => 'products.index',
                                'label' => 'Products',
                                'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                            ]
                            : null,
                        $features->enabled('purchases') && $user->can(PermissionEnum::PurchasesView->value)
                            ? [
                                'route' => 'purchases.index',
                                'label' => 'Purchases',
                                'icon' =>
                                    'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
                            ]
                            : null,
                        $features->enabled('suppliers') && $user->can(PermissionEnum::SuppliersManage->value)
                            ? [
                                'route' => 'suppliers.index',
                                'label' => 'Suppliers',
                                'icon' =>
                                    'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                            ]
                            : null,
                        $features->enabled('used_phones') && $user->can(PermissionEnum::UsedPhonesView->value)
                            ? [
                                'route' => 'used-phones.index',
                                'label' => 'Used Phones',
                                'icon' =>
                                    'M12 18h.01M8 21l4-4 4 4M3 4.5A2.5 2.5 0 015.5 2h13A2.5 2.5 0 0121 4.5v9.5a2.5 2.5 0 01-2.5 2.5h-13A2.5 2.5 0 013 14V4.5z',
                            ]
                            : null,
                        $features->enabled('emi_partners') && $user->can(PermissionEnum::EmiView->value)
                            ? [
                                'route' => 'finance-partners.index',
                                'label' => 'EMI Partners',
                                'icon' =>
                                    'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
                            ]
                            : null,
                        $features->enabled('service') && $user->can(PermissionEnum::ServiceView->value)
                            ? [
                                'route' => 'service.index',
                                'label' => 'Service',
                                'icon' =>
                                    'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                            ]
                            : null,
                        $features->enabled('expenses') && $user->can(PermissionEnum::ExpensesView->value)
                            ? [
                                'route' => 'expenses.index',
                                'label' => 'Expenses',
                                'icon' =>
                                    'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2',
                            ]
                            : null,
                        $features->enabled('payroll') && $user->can(PermissionEnum::PayrollView->value)
                            ? [
                                'route' => 'payroll.index',
                                'label' => 'Payroll',
                                'icon' =>
                                    'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                            ]
                            : null,
                        $features->enabled('employees') && $user->can(PermissionEnum::EmployeesView->value)
                            ? [
                                'route' => 'employees.index',
                                'label' => 'Employees',
                                'icon' =>
                                    'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
                            ]
                            : null,
                        $features->enabled('treasury') && $user->can(PermissionEnum::TreasuryView->value)
                            ? [
                                'route' => 'treasury.index',
                                'label' => 'Treasury',
                                'icon' =>
                                    'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                            ]
                            : null,

                        // REPORTS SECTION
                        $features->enabled('reports') && $canViewAnyReport
                            ? ['divider' => true, 'label' => 'REPORTS']
                            : null,

                        $features->enabled('reports') && $user->can(PermissionEnum::AccountingViewFullReports->value)
                            ? [
                                'route' => 'reports.pl',
                                'label' => 'P&L',
                                'icon' =>
                                    'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                            ]
                            : null,
                        $features->enabled('reports') && $user->can(PermissionEnum::AccountingViewFullReports->value)
                            ? [
                                'route' => 'reports.trial-balance',
                                'label' => 'Trial Balance',
                                'icon' =>
                                    'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
                            ]
                            : null,
                        $features->enabled('reports') && $user->can(PermissionEnum::AccountingViewFullReports->value)
                            ? [
                                'route' => 'reports.balance-sheet',
                                'label' => 'Balance Sheet',
                                'icon' =>
                                    'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                            ]
                            : null,
                        $features->enabled('reports') && $user->can(PermissionEnum::AccountingViewFullReports->value)
                            ? [
                                'route' => 'reports.general-ledger',
                                'label' => 'Ledger',
                                'icon' =>
                                    'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
                            ]
                            : null,
                        $features->enabled('reports') && $user->can(PermissionEnum::AccountingViewBasicReports->value)
                            ? [
                                'route' => 'reports.sales',
                                'label' => 'Sales Rpt',
                                'icon' =>
                                    'M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
                            ]
                            : null,
                        $features->enabled('reports') && $user->can(PermissionEnum::AccountingViewBasicReports->value)
                            ? [
                                'route' => 'reports.stock',
                                'label' => 'Stock',
                                'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                            ]
                            : null,
                        $features->enabled('reports') && $user->can(PermissionEnum::AccountingViewBasicReports->value)
                            ? [
                                'route' => 'reports.customer-due',
                                'label' => 'Due Rpt',
                                'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
                            ]
                            : null,
                        $features->enabled('reports') && $user->can(PermissionEnum::AccountingViewFullReports->value)
                            ? [
                                'route' => 'reports.account-statement',
                                'label' => 'Acc Stmt',
                                'icon' =>
                                    'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                            ]
                            : null,
                        $features->enabled('reports') && $user->can(PermissionEnum::AccountingViewFullReports->value)
                            ? [
                                'route' => 'reports.cash-flow',
                                'label' => 'Cash Flow',
                                'icon' => 'M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4',
                            ]
                            : null,
                        $features->enabled('reports') && $user->can(PermissionEnum::AccountingViewBasicReports->value)
                            ? [
                                'route' => 'reports.service',
                                'label' => 'Service Rpt',
                                'icon' =>
                                    'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
                            ]
                            : null,
                        $features->enabled('reports') && $user->can(PermissionEnum::AccountingViewBasicReports->value)
                            ? [
                                'route' => 'reports.imei-ledger',
                                'label' => 'IMEI Ledger',
                                'icon' =>
                                    'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                            ]
                            : null,

                        // Assume everyone can view notifications (or map it to a specific permission if you prefer)
                        [
                            'route' => 'notifications.index',
                            'label' => 'Notifications',
                            'icon' =>
                                'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
                        ],
                        $features->enabled('settings') && $user->can(PermissionEnum::SettingsManage->value)
                            ? [
                                'route' => 'settings',
                                'label' => 'Settings',
                                'icon' =>
                                    'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                            ]
                            : null,
                    ]);
                @endphp

                @foreach (array_values($navLinks) as $link)
                    @if (isset($link['divider']))
                        <div class="px-3 pt-4 pb-1">
                            <span
                                class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ $link['label'] }}</span>
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
            <div class="p-3 border-t border-gray-100 dark:border-gray-700 shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium
                                   text-gray-600 dark:text-gray-400 hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 transition-colors">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.75"
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
        <div class="flex-1 flex flex-col min-w-0">

            {{-- ── TOP BAR ── --}}
            <header
                class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8 dark:bg-gray-800 dark:border-gray-700">

                {{-- Hamburger (mobile) --}}
                <button @click="sidebarOpen = true"
                    class="-m-2.5 p-2.5 text-gray-700 dark:text-gray-300 lg:hidden hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                        aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                {{-- Separator (mobile) --}}
                <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 lg:hidden" aria-hidden="true"></div>

                {{-- Header Content Area --}}
                <div class="flex flex-1 justify-between items-center gap-x-4 self-stretch lg:gap-x-6">

                    {{-- Left Side: Global Search --}}
                    <div class="flex flex-1 items-center">
                        @livewire('global-search')
                    </div>

                    {{-- Right Side: Notifications, Warning & User Avatar --}}
                    <div class="flex items-center gap-x-4 lg:gap-x-6">

                        {{-- Balance Warning / Alerts --}}
                        @if (session('balance_warning'))
                            <div
                                class="text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 dark:bg-amber-900/30 dark:border-amber-700 dark:text-amber-400 rounded-lg px-3 py-1.5 max-w-xs truncate hidden sm:block">
                                ⚠ {{ session('balance_warning') }}
                            </div>
                        @endif

                        @livewire('notifications.notification-bell')

                        {{-- Separator --}}
                        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200 dark:lg:bg-gray-700"
                            aria-hidden="true"></div>

                        {{-- User avatar with dropdown --}}
                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                            <button @click="open = !open"
                                class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold shrink-0 hover:bg-indigo-700 transition-colors">
                                {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                            </button>
                            <div x-show="open" x-transition
                                class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-50"
                                style="display:none;">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <div class="text-xs font-semibold text-gray-900 truncate">
                                        {{ auth()->user()?->name }}</div>
                                    <div class="text-xs text-gray-400 truncate">{{ auth()->user()?->email }}</div>
                                </div>
                                <a href="{{ route('profile') }}" wire:navigate @click="open = false"
                                    class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                    👤 My Profile
                                </a>
                                @if (auth()->user()?->isOwner())
                                    <a href="{{ route('settings') }}" wire:navigate @click="open = false"
                                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                        ⚙ Settings
                                    </a>
                                @endif
                                <div class="border-t border-gray-100 mt-1">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                            🚪 Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </header>

            {{-- ── PAGE CONTENT ── --}}
            <main class="flex-1 overflow-y-auto">
                <div class="p-4 sm:p-5 lg:p-6 max-w-7xl mx-auto">
                    <x-announcement-banner audience="shop_app" />
                    {{ $slot }}
                </div>
                {{-- ── FOOTER ── --}}
                <footer
                    class="mt-auto py-4 text-center text-sm text-gray-500 dark:text-gray-400 border-t border-gray-200 dark:border-gray-700">
                    &copy; {{ date('Y') }} <span
                        class="font-semibold text-indigo-600 dark:text-indigo-400">SmartShop ERP</span>. Developed by
                    <span class="font-medium text-gray-700 dark:text-gray-300">Hasan Sarkar</span>.
                </footer>
            </main>

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
        class="fixed bottom-4 right-4 z-[200] space-y-3 max-w-sm w-full px-4 sm:px-0 pointer-events-none">

        <template x-for="n in notifications" :key="n.id">
            <div x-show="true" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 sm:scale-100" x-transition:leave-end="opacity-0 sm:scale-95"
                :class="{
                    'bg-green-600': n.type === 'success',
                    'bg-red-600': n.type === 'error',
                    'bg-amber-500': n.type === 'warning',
                    'bg-indigo-600': n.type === 'info' || !n.type,
                }"
                class="pointer-events-auto flex items-start gap-3 px-4 py-3 rounded-xl shadow-lg ring-1 ring-black ring-opacity-5 text-white text-sm">
                <span x-text="n.message" class="flex-1 font-medium leading-snug"></span>
                <button @click="remove(n.id)"
                    class="shrink-0 opacity-70 hover:opacity-100 focus:outline-none transition-opacity">✕</button>
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
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/10.7.1/firebase-messaging-compat.js"></script>
    <script>
        const firebaseConfig = {
            apiKey: "{{ env('VITE_FIREBASE_API_KEY') }}",
            projectId: "{{ env('VITE_FIREBASE_PROJECT_ID') }}",
            messagingSenderId: "{{ env('VITE_FIREBASE_MESSAGING_SENDER_ID') }}",
            appId: "{{ env('VITE_FIREBASE_APP_ID') }}"
        };

        firebase.initializeApp(firebaseConfig);
        const messaging = firebase.messaging();

        async function registerFcmToken() {
            try {
                const permission = await Notification.requestPermission();
                if (permission === 'granted') {
                    const registration = await navigator.serviceWorker.register('/sw.js');
                    const token = await messaging.getToken({
                        vapidKey: "{{ env('VITE_FIREBASE_VAPID_KEY') }}",
                        serviceWorkerRegistration: registration,
                    });

                    await fetch('/api/device-token', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            token: token,
                            platform: 'web',
                            device_name: navigator.userAgent,
                            app_version: '1.0.0'
                        })
                    });
                }
            } catch (err) {
                console.error('FCM Registration Failed:', err);
            }
        }

        @auth
        // Small delay to ensure page is loaded
        window.addEventListener('load', () => registerFcmToken());
        @endauth
    </script>

</body>

</html>
