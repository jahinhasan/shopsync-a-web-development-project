<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;

class AdminController extends Controller
{
    public function dashboard(): View
    {
        $totalUsers = User::count();
        $totalProducts = Product::count();
        $totalCategories = Category::count();
        $totalOrders = Order::count();

        // Item-level statuses are the authoritative order lifecycle
        // (orders.status stays 'pending' after creation), so sales are
        // aggregated from order items the same way the owner dashboard is.
        $salesStatuses = ['processing', 'shipped', 'delivered'];

        $totalSales = OrderIdem::query()
            ->whereIn('status', $salesStatuses)
            ->sum('subtotal');

        $recentOrders = Order::query()
            ->with('user')
            ->latest()
            ->take(6)
            ->get();

        $lowStockProducts = Product::query()
            ->with('owner')
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->orderBy('name')
            ->take(8)
            ->get();

        $roleCounts = User::query()
            ->selectRaw('role, COUNT(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        return view('dashboards.admin', compact(
            'totalUsers',
            'totalProducts',
            'totalCategories',
            'totalOrders',
            'totalSales',
            'recentOrders',
            'lowStockProducts',
            'roleCounts',
        ));
    }
}
