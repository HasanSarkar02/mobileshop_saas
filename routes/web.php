<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Admin\CreateShop;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\ShopDetail;
use App\Livewire\Admin\ShopList;
use App\Livewire\Purchases\CreatePurchase;
use App\Livewire\Purchases\PurchaseDetail;
use App\Livewire\Purchases\PurchaseList;
use App\Livewire\Suppliers\SupplierForm;
use App\Livewire\Suppliers\SupplierList;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\ImpersonationController;
use App\Livewire\Products\ProductDetail;
use App\Livewire\Products\ProductForm;
use App\Livewire\Products\ProductList;
use App\Livewire\Settings\ShopSettings;
use App\Livewire\Customers\CustomerForm;
use App\Livewire\Customers\CustomerList;
use App\Livewire\Customers\CustomerProfile;
use App\Http\Controllers\SaleReceiptController;
use App\Livewire\Sales\SaleList;

// ─── Super Admin ──────────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->group(function () {

    // Guest-facing (login page) — no middleware, controller handles already-logged-in redirect
    Route::get('login', [AdminLoginController::class, 'create'])->name('login');
    Route::post('login', [AdminLoginController::class, 'store'])->name('login.store');

    // Protected admin routes — must be authenticated via the admin guard
    Route::middleware('super_admin')->group(function () {
        Route::post('logout', [AdminLoginController::class, 'destroy'])->name('logout');
        Route::get('dashboard', Dashboard::class)->name('dashboard');
        Route::get('shops', ShopList::class)->name('shops.index');
        Route::get('shops/create', CreateShop::class)->name('shops.create');
        Route::get('shops/{shop}', ShopDetail::class)->name('shops.show');
        Route::post('impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
    });
});

// ─── Shop App (Owner + Employee) ──────────────────────────────────────────────
// ─── Shop App (Owner + Employee) ──────────────────────────────────────────────
Route::middleware(['auth:web'])->group(function () {

    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::livewire('dashboard', \App\Livewire\Dashboard::class)->name('dashboard');

    // Impersonation stop
    Route::post('impersonation/stop', [ImpersonationController::class, 'stop'])->name('impersonation.stop');

    // Products
    Route::livewire('products', ProductList::class)->name('products.index');
    Route::livewire('products/create', ProductForm::class)->name('products.create');
    Route::livewire('products/{product}/edit', ProductForm::class)->name('products.edit');
    Route::livewire('products/{product}', ProductDetail::class)->name('products.show');

    // Suppliers
    Route::livewire('suppliers', SupplierList::class)->name('suppliers.index');
    Route::livewire('suppliers/create', SupplierForm::class)->name('suppliers.create');
    Route::livewire('suppliers/{supplier}/edit', SupplierForm::class)->name('suppliers.edit');

    // Purchases
    Route::livewire('purchases', PurchaseList::class)->name('purchases.index');
    Route::livewire('purchases/create', CreatePurchase::class)->name('purchases.create');
    Route::livewire('purchases/{purchase}', PurchaseDetail::class)->name('purchases.show');

    // Settings
    Route::livewire('settings', ShopSettings::class)->name('settings');


    // Customers
    Route::livewire('customers', CustomerList::class)->name('customers.index');
    Route::livewire('customers/create', CustomerForm::class)->name('customers.create');
    Route::livewire('customers/{customer}/edit', CustomerForm::class)->name('customers.edit');
    Route::livewire('customers/{customer}', CustomerProfile::class)->name('customers.show');

    // POS
    Route::livewire('pos', \App\Livewire\Pos::class)->name('pos');

    // Sales
    Route::livewire('sales', SaleList::class)->name('sales.index');
    Route::get('sales/{sale}/receipt', [SaleReceiptController::class, 'show'])
        ->name('sales.receipt');
});