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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;


class ReportsController extends Controller
{
    public function admin(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);

        $salesStatuses = ['processing', 'shipped', 'delivered'];

        $orders = Order::whereBetween('created_at', [
            $from->copy()->startOfDay(),
            $to->copy()->endOfDay(),
        ]);

        $totalOrders = (clone $orders)->count();

        // Item-level statuses are the authoritative order lifecycle, so all
        // sales figures aggregate order items (same definition as the owner
        // report) within the report's order date range. The status column is
        // qualified because the daily/monthly queries join orders.
        $salesItems = OrderIdem::query()
            ->whereIn('order_idems.status', $salesStatuses)
            ->whereHas('order', function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [
                    $from->copy()->startOfDay(),
                    $to->copy()->endOfDay(),
                ]);
            });

        $totalSales = (clone $salesItems)->sum('subtotal');

        $dailySales = (clone $salesItems)
            ->join('orders', 'order_idems.order_id', '=', 'orders.id')
            ->selectRaw('DATE(orders.created_at) as sale_date, SUM(order_idems.subtotal) as sales, COUNT(DISTINCT order_idems.order_id) as orders_count')
            ->groupByRaw('DATE(orders.created_at)')
            ->orderBy('sale_date')
            ->get();

        // DATE_FORMAT is MySQL-only; SQLite (local/test) uses strftime.
        $monthExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', orders.created_at)"
            : "DATE_FORMAT(orders.created_at, '%Y-%m')";

        $monthlySales = (clone $salesItems)
            ->join('orders', 'order_idems.order_id', '=', 'orders.id')
            ->selectRaw("{$monthExpression} as sale_month, SUM(order_idems.subtotal) as sales, COUNT(DISTINCT order_idems.order_id) as orders_count")
            ->groupByRaw($monthExpression)
            ->orderBy('sale_month')
            ->get();

        $bestSellingProducts = (clone $salesItems)
            ->selectRaw('product_id, MAX(product_name) as product_name, SUM(quantity) as quantity_sold, SUM(subtotal) as revenue')
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
            ->whereIn('status', $salesStatuses)
            ->sum('subtotal');

        $totalItemsSold = (clone $orderItems)
            ->whereIn('status', $salesStatuses)
            ->sum('quantity');

        $totalOrders = (clone $orderItems)
            ->whereIn('status', $salesStatuses)
            ->distinct()
            ->count('order_id');

        $dailySales = (clone $orderItems)
            ->whereIn('order_idems.status', $salesStatuses)
            ->whereHas('order', function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [
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
            ->whereIn('status', $salesStatuses)
            ->whereHas('order', function ($query) use ($from, $to) {
                $query->whereBetween('created_at', [
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
