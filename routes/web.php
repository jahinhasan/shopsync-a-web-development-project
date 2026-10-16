
<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OderController;
use App\Http\Controllers\Owner\DashboardController;
use App\Http\Controllers\Owner\InventoryController;
use App\Http\Controllers\Owner\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\ShopApplicationController;
use App\Http\Controllers\Admin\ShopApplicationController as AdminShopApplicationController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\UserNotificationController;

Route::get('/', function () {
    return redirect()->route('login');
})->name('home');

Route::middleware('auth')->group(function () {

    // Role-based dashboard redirect
    Route::get('/dashboard', fn() => match (request()->user()->role) {
        'admin' => redirect()->route('admin.dashboard'),
        'business_owner' => redirect()->route('owner.dashboard'),
        default => redirect()->route('customer.dashboard'),
    })->middleware('verified')->name('dashboard');

    // Storefront
    Route::get('/shop', [StorefrontController::class, 'index'])
        ->name('shop.index');

    Route::get('/shop/{product}', [StorefrontController::class, 'show'])
        ->name('shop.show');
        Route::get('/notifications', [
    UserNotificationController::class,
    'index',
])->name('notifications.index');

Route::patch('/notifications/{notificationId}/read', [
    UserNotificationController::class,
    'markAsRead',
])->name('notifications.read');

Route::patch('/notifications/read-all', [
    UserNotificationController::class,
    'markAllAsRead',
])->name('notifications.read-all');

    // Cart
    Route::get('/cart', [CartController::class, 'index'])
        ->name('cart.index');

    Route::post('/cart/{product}', [CartController::class, 'add'])
        ->name('cart.add');

    Route::patch('/cart/{product}', [CartController::class, 'update'])
        ->name('cart.update');

    Route::delete('/cart/{product}', [CartController::class, 'remove'])
        ->name('cart.remove');

    // Checkout
    Route::get('/checkout', [CartController::class, 'checkout'])
        ->name('checkout.index');

    Route::post('/checkout', [CartController::class, 'placeOrder'])
        ->name('checkout.place');

    // Customer orders
    Route::get('/my-orders', [OderController::class, 'index'])
        ->name('orders.index');

    Route::get('/my-orders/{order}', [OderController::class, 'show'])
        ->name('orders.show');

    // Customer shopping dashboard
    Route::get('/customer/dashboard', [
        \App\Http\Controllers\Customer\DashboardController::class,
        'index',
    ])
        ->middleware('role:customer')
        ->name('customer.dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    // Business owner routes
    Route::middleware(['role:business_owner',  'shop.approved',])
        ->prefix('owner')
        ->name('owner.')
        ->group(function () {

            Route::get('/dashboard', [DashboardController::class, 'index'])
                ->name('dashboard');


            Route::get('/shop-application', [ShopApplicationController::class, 'create'])
                ->name('shop-application.create');

            Route::post('/shop-application', [ShopApplicationController::class, 'store'])
                ->name('shop-application.store');

            Route::get('/shop-application/status', [ShopApplicationController::class, 'status'])
                ->name('shop-application.status');

            Route::resource('products', ProductController::class)
                ->except('show');

            Route::patch('/products/{product}/stock', [
                InventoryController::class,
                'updateStock',
            ])->name('products.update-stock');

            Route::get('/inventory/history', [
                InventoryController::class,
                'history',
            ])->name('inventory.history');

            // Manage orders belonging to this business owner
            Route::get('/manage/orders', [
                OderController::class,
                'manage',
            ])->name('order.manage');

            Route::patch('/manage/orders/{order}/status', [
                OderController::class,
                'updateStatus',
            ])->name('orders.update-status');

            Route::get('/reports', [
                \App\Http\Controllers\ReportsController::class,
                'owner',
            ])->name('reports');
        });

    // Admin dashboard — only one route
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard'])
        ->middleware('role:admin')
        ->name('admin.dashboard');

    // Admin category management
    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::resource('categories', CategoryController::class)
                ->except('show');

            Route::get('/shop-applications', [
                AdminShopApplicationController::class,
                'index',
            ])->name('shop-applications.index');

            Route::get('/shop-applications/{application}', [
                AdminShopApplicationController::class,
                'show',
            ])->name('shop-applications.show');

            Route::patch('/shop-applications/{application}/approve', [
                AdminShopApplicationController::class,
                'approve',
            ])->name('shop-applications.approve');

            Route::patch('/shop-applications/{application}/request-information', [
                AdminShopApplicationController::class,
                'requestInformation',
            ])->name('shop-applications.request-information');

            Route::patch('/shop-applications/{application}/reject', [
                AdminShopApplicationController::class,
                'reject',
            ])->name('shop-applications.reject');

            Route::get('/shop-applications/{application}/documents/{document}', [
                AdminShopApplicationController::class,
                'document',
            ])->whereIn('document', ['identity-photo', 'identity-document'])
                ->name('shop-applications.documents');

            Route::get('/shop-applications/{application}/media/{mediaId}', [
                AdminShopApplicationController::class,
                'media',
            ])->whereNumber('mediaId')
                ->name('shop-applications.media');

            // Admin product and shop moderation
            Route::get('/moderation', [
                ModerationController::class,
                'index',
            ])->name('moderation.index');

            Route::patch('/moderation/products/{productId}/suspend', [
                ModerationController::class,
                'suspendProduct',
            ])->whereNumber('productId')->name('moderation.products.suspend');

            Route::patch('/moderation/products/{productId}/warn', [
                ModerationController::class,
                'warnProductOwner',
            ])->whereNumber('productId')->name('moderation.products.warn');

            Route::delete('/moderation/products/{productId}', [
                ModerationController::class,
                'removeProduct',
            ])->whereNumber('productId')->name('moderation.products.remove');

            Route::patch('/moderation/products/{productId}/restore', [
                ModerationController::class,
                'restoreProduct',
            ])->whereNumber('productId')->name('moderation.products.restore');

            Route::patch('/moderation/shops/{applicationId}/status', [
                ModerationController::class,
                'updateShopStatus',
            ])->whereNumber('applicationId')->name('moderation.shops.status');
        });

    Route::get('/admin/reports', [
        \App\Http\Controllers\ReportsController::class,
        'admin',
    ])->middleware('role:admin')->name('admin.reports');
});

require __DIR__ . '/auth.php';
