<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;


class ReportsController extends Controller
{
    public function admin(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);

        $orders = Order::whereBetween('created_at', [
            $from->copy()->startOfDay(),
            $to->copy()->endOfDay(),
        ]);

        $salesStatuses = ['processing', 'shipped', 'delivered'];

        $totalOrders = (clone $orders)->count();

        $totalSales = (clone $orders)
            ->whereIn('status', $salesStatuses)
            ->sum('total');

        $dailySales = (clone $orders)
            ->whereIn('status', $salesStatuses)
            ->selectRaw('DATE(created_at) as sale_date, SUM(total) as sales, COUNT(*) as orders_count')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('sale_date')
            ->get();

        $monthlySales = Order::whereIn('status', $salesStatuses)
            ->whereBetween('created_at', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as sale_month, SUM(total) as sales, COUNT(*) as orders_count")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderBy('sale_month')
            ->get();

        $bestSellingProducts = OrderIdem::query()
            ->selectRaw('product_id, MAX(product_name) as product_name, SUM(quantity) as quantity_sold, SUM(subtotal) as revenue')
            ->whereHas('order', function ($query) use ($from, $to, $salesStatuses) {
                $query->whereIn('status', $salesStatuses)
                    ->whereBetween('created_at', [
                        $from->copy()->startOfDay(),
                        $to->copy()->endOfDay(),
                    ]);
            })
            ->groupBy('product_id')
            ->orderByDesc('quantity_sold')
            ->take(10)
            ->get();

        $lowStockProducts = Product::with('owner')
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->take(20)
            ->get();

        return view('reports.admin', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'totalUsers' => User::count(),
            'totalProducts' => Product::count(),
            'totalCategories' => Category::count(),
            'totalOrders' => $totalOrders,
            'totalSales' => $totalSales,
            'dailySales' => $dailySales,
            'monthlySales' => $monthlySales,
            'bestSellingProducts' => $bestSellingProducts,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }

    public function owner(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);

        $ownerId = Auth::id();

        $orderItems = OrderIdem::query()
            ->whereHas('product', function ($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })
            ->whereHas('order', function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [
                    $from->copy()->startOfDay(),
                    $to->copy()->endOfDay(),
                ]);
            });

        $salesStatuses = ['processing', 'shipped', 'delivered'];

        $totalSales = (clone $orderItems)
            ->whereHas('order', function ($query) use ($salesStatuses) {
                $query->whereIn('status', $salesStatuses);
            })
            ->sum('subtotal');

        $totalItemsSold = (clone $orderItems)
            ->whereHas('order', function ($query) use ($salesStatuses) {
                $query->whereIn('status', $salesStatuses);
            })
            ->sum('quantity');

        $totalOrders = (clone $orderItems)
            ->whereHas('order', function ($query) use ($salesStatuses) {
                $query->whereIn('status', $salesStatuses);
            })
            ->distinct('order_id')
            ->count('order_id');

        $dailySales = (clone $orderItems)
            ->whereHas('order', function ($query) use ($from, $to, $salesStatuses) {
                $query->whereIn('status', $salesStatuses)
                    ->whereBetween('created_at', [
                        $from->copy()->startOfDay(),
                        $to->copy()->endOfDay(),
                    ]);
            })
            ->join('orders', 'order_idems.order_id', '=', 'orders.id')
            ->selectRaw('DATE(orders.created_at) as sale_date, SUM(order_idems.subtotal) as sales, SUM(order_idems.quantity) as quantity_sold')
            ->groupByRaw('DATE(orders.created_at)')
            ->orderBy('sale_date')
            ->get();

        $bestSellingProducts = (clone $orderItems)
            ->whereHas('order', function ($query) use ($from, $to, $salesStatuses) {
                $query->whereIn('status', $salesStatuses)
                    ->whereBetween('created_at', [
                        $from->copy()->startOfDay(),
                        $to->copy()->endOfDay(),
                    ]);
            })
            ->selectRaw('product_id, MAX(product_name) as product_name, SUM(quantity) as quantity_sold, SUM(subtotal) as revenue')
            ->groupBy('product_id')
            ->orderByDesc('quantity_sold')
            ->take(10)
            ->get();

        $lowStockProducts = Product::where('user_id', $ownerId)
            ->where('stock', '<=', 5)
            ->orderBy('stock')
            ->get();

        $inventoryTransactions = InventoryTransaction::where('user_id', $ownerId)
            ->with('product')
            ->latest()
            ->take(10)
            ->get();

        return view('reports.owner', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'totalSales' => $totalSales,
            'totalItemsSold' => $totalItemsSold,
            'totalOrders' => $totalOrders,
            'dailySales' => $dailySales,
            'bestSellingProducts' => $bestSellingProducts,
            'lowStockProducts' => $lowStockProducts,
            'inventoryTransactions' => $inventoryTransactions,
        ]);
    }

    private function dateRange(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString());
        $to = Carbon::parse($validated['to'] ?? now()->toDateString());

        if ($from->greaterThan(now()->endOfDay())) {
            throw ValidationException::withMessages([
                'from' => 'Start date cannot be in the future.',
            ]);
        }

        return [$from, $to];
    }


}
