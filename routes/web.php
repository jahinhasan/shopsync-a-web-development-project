<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn () => match (auth()->user()->role){
        'admin' => redirect()->route('admin.dashboard'),
        'business_owner' => redirect()->route('owner.dashboard'),
        default => redirect()->route('customer.dashboard'),
    })->middleware('verified')->name('dashboard');

  Route::view('/customer/dashboard', 'dashboards.customer')->middleware('role:customer')->name('customer.dashboard');
    Route::view('/owner/dashboard', 'dashboards.owner')->middleware('role:business_owner')->name('owner.dashboard');
    Route::view('/admin/dashboard', 'dashboards.admin')->middleware('role:admin')->name('admin.dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__.'/auth.php';
