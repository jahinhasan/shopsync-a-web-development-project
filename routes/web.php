<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Owner\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OderController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\InventoryController;

Route::view('/', 'welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn() => match (request()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'business_owner' => redirect()->route('owner.dashboard'),
        default => redirect()->route('customer.dashboard'),
    })->middleware('verified')->name('dashboard');
    Route::put('/products/{product}', [
        ProductController::class,
        'update',
    ])->name('products.update');
    Route::get('/shop', [StorefrontController::class, 'index'])->name('shop.index');
    Route::get('/shop/{product}', [StorefrontController::class, 'show'])->name('shop.show');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/cart', [CartController::class, 'index'])
        ->name('cart.index');

    Route::post('/cart/{product}', [CartController::class, 'add'])
        ->name('cart.add');

    Route::delete('/cart/{product}', [CartController::class, 'remove'])
        ->name('cart.remove');



    Route::patch('/cart/{product}', [CartController::class, 'update'])
        ->name('cart.update');

    Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout.index');
    Route::post('/checkout', [CartController::class, 'placeOrder'])->name('checkout.place');

    Route::get('/my-orders', [OderController::class, 'index'])->name('orders.index');
    Route::get('/my-orders/{order}', [OderController::class, 'show'])->name('orders.show');

    Route::get('/manage/orders', [OderController::class, 'manage'])->name('orders.manage');
    Route::patch('/manage/orders/{order}/status', [OderController::class, 'updateStatus'])->name('orders.update-status');
    Route::view('/customer/dashboard', 'dashboards.customer')->middleware('role:customer')->name('customer.dashboard');
    Route::get('/owner/dashboard', [DashboardController::class, 'index'])
        ->middleware('role:business_owner')
        ->name('owner.dashboard');
    Route::view('/admin/dashboard', 'dashboards.admin')->middleware('role:admin')->name('admin.dashboard');
    Route::patch('/products/{product}/stock', [
        InventoryController::class,
        'updateStock',
    ])->name('products.update-stock');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');



Route::middleware(['auth', 'role:business_owner'])
    ->prefix('owner')
    ->name('owner.')
    ->group(function () {
        Route::resource('products', ProductController::class)
            ->except('show');

        Route::patch('/products/{product}/stock', [
            InventoryController::class,
            'updateStock',
        ])->name('products.update-stock');
        Route::get('/inventory/history', [InventoryController::class, 'history'])
   
    ->name('inventory.history');
    });

    Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(fn() => Route::resource('categories', CategoryController::class)->except('show'));
    Route::middleware('auth')->get('/admin/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
});

$app = require __DIR__ . '/auth.php';
