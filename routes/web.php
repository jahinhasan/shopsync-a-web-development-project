<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Owner\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorefrontController;

Route::view('/', 'welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => match (request()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'business_owner' => redirect()->route('owner.dashboard'),
        default => redirect()->route('customer.dashboard'),
    })->middleware('verified')->name('dashboard');
    Route::put('/products/{product}', [
        ProductController::class,
        'update',
    ])->name('products.update');
    Route::get('/shop', [StorefrontController::class, 'index'])->name('shop.index');

    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::view('/customer/dashboard', 'dashboards.customer')->middleware('role:customer')->name('customer.dashboard');
    Route::view('/owner/dashboard', 'dashboards.owner')->middleware('role:business_owner')->name('owner.dashboard');
    Route::view('/admin/dashboard', 'dashboards.admin')->middleware('role:admin')->name('admin.dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['auth', 'role:business_owner'])->prefix('owner')->name('owner.')->group(fn () => Route::resource('products', ProductController::class)->except('show'));
    Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(fn () => Route::resource('categories', CategoryController::class)->except('show'));
});

$app = require __DIR__.'/auth.php';
