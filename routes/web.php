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
Route::middleware(['auth:web'])->group(function () {

    Route::get('/', fn() => redirect()->route('dashboard'));
    Route::get('dashboard', fn() => view('dashboard'))->name('dashboard');

    // Impersonation
    Route::post('impersonation/stop', [ImpersonationController::class, 'stop'])->name('impersonation.stop');

    // Suppliers
    Route::get('suppliers', SupplierList::class)->name('suppliers.index');
    Route::get('suppliers/create', SupplierForm::class)->name('suppliers.create');
    Route::get('suppliers/{supplier}/edit', SupplierForm::class)->name('suppliers.edit');

    // Purchases
    Route::get('purchases', PurchaseList::class)->name('purchases.index');
    Route::get('purchases/create', CreatePurchase::class)->name('purchases.create');
    Route::get('purchases/{purchase}', PurchaseDetail::class)->name('purchases.show');
});