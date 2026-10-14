<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(): View
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

        $totalSales = (clone $ownerOrdersQuery)
            ->whereIn('status', $salesStatuses)
            ->sum('total');

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

        $pendingOrders = (clone $ownerOrdersQuery)
            ->whereIn('status', ['pending', 'processing'])
            ->distinct()
            ->count('orders.id');

        $todaySales = (clone $ownerOrdersQuery)
            ->whereIn('status', $salesStatuses)
            ->whereDate('orders.created_at', today())
            ->sum('total');

        $todayOrders = (clone $ownerOrdersQuery)
            ->whereDate('orders.created_at', today())
            ->distinct()
            ->count('orders.id');

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
        ));
    }
}
