<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Services\Analytics\AnalyticsService;
use App\Services\Analytics\WindowResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request, AnalyticsService $analytics): View
    {
        $userId = Auth::id();

        $productsQuery = Product::query()
            ->where('user_id', $userId);

        $ownerOrdersQuery = Order::query()
            ->whereHas('items.product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            });

        $salesStatuses = ['processing', 'shipped', 'delivered'];

        $totalProducts = (clone $productsQuery)->count();

        $totalStock = (clone $productsQuery)->sum('stock');

        $totalOrders = (clone $ownerOrdersQuery)
            ->distinct()
            ->count('orders.id');

        $ownerOrderItems = OrderIdem::query()
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereIn('status', $salesStatuses);

        $totalSales = (clone $ownerOrderItems)->sum('subtotal');

        $lowStockProducts = (clone $productsQuery)
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->orderBy('name')
            ->take(6)
            ->get();

        $recentOrders = (clone $ownerOrdersQuery)
            ->with(['user', 'items' => function ($query) use ($userId) {
                $query->whereHas('product', function ($productQuery) use ($userId) {
                    $productQuery->where('user_id', $userId);
                })->with('product');
            }])
            ->latest('orders.created_at')
            ->take(6)
            ->get();

        $pendingOrders = OrderIdem::query()
            ->whereHas('product', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->whereIn('status', ['pending', 'processing'])
            ->distinct()
            ->count('order_id');

        $todaySales = (clone $ownerOrderItems)
            ->whereHas('order', function ($query) {
                $query->whereDate('created_at', today());
            })
            ->sum('subtotal');

        $todayOrders = (clone $ownerOrdersQuery)
            ->whereDate('orders.created_at', today())
            ->distinct()
            ->count('orders.id');

        // All-time average order value with a consistent denominator: net
        // sales divided by the orders that actually produced those sales.
        $qualifyingOrderCount = (clone $ownerOrderItems)
            ->distinct()
            ->count('order_id');

        $averageOrderValue = $qualifyingOrderCount > 0
            ? round((float) $totalSales / $qualifyingOrderCount, 2)
            : 0.0;

        $ownerId = (int) $userId;

        // Window-scoped analytics scoped strictly to this owner's products
        // (scope comes from Auth::id(), never from the request). Comparison
        // is always on so KPI delta chips are honest, and the cache key
        // carries both the window and the owner id.
        $window = WindowResolver::dashboard($request);

        $trend = Cache::remember(
            'analytics:owner:dashboard:'.$window['window'].':'.$ownerId,
            now()->addMinutes(10),
            fn (): array => $analytics->overview(
                $ownerId,
                $window['from'],
                $window['to'],
                $window['granularity'],
                true,
            ),
        );

        return view('dashboards.owner', compact(
            'totalProducts',
            'totalStock',
            'totalOrders',
            'totalSales',
            'lowStockProducts',
            'recentOrders',
            'pendingOrders',
            'todaySales',
            'todayOrders',
            'averageOrderValue',
            'trend',
            'window',
        ));
    }
}
