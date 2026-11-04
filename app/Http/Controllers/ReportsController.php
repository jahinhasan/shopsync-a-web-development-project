<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\User;
use App\Services\Analytics\AnalyticsCsv;
use App\Services\Analytics\AnalyticsService;
use App\Services\Analytics\WindowResolver;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function admin(Request $request, AnalyticsService $analytics): View|StreamedResponse
    {
        $window = WindowResolver::resolve($request);

        $payload = $this->cachedOverview(
            $analytics,
            'admin',
            null,
            $window['from'],
            $window['to'],
            $window['granularity'],
            $window['compare'],
        );

        if ($request->input('export') === 'csv') {
            return AnalyticsCsv::stream($payload);
        }

        $salesStatuses = AnalyticsService::SALES_STATUSES;

        $salesItems = OrderIdem::query()
            ->whereIn('order_idems.status', $salesStatuses)
            ->whereHas('order', function ($query) use ($window) {
                $query->whereBetween('created_at', [
                    $window['from']->copy()->startOfDay(),
                    $window['to']->copy()->endOfDay(),
                ]);
            });

        // DATE_FORMAT is MySQL-only; SQLite (local/test) uses strftime.
        $monthExpression = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', orders.created_at)"
            : "DATE_FORMAT(orders.created_at, '%Y-%m')";

        // All-time by design — the heading and the legacy report both say so.
        $monthlySales = OrderIdem::query()
            ->whereIn('order_idems.status', $salesStatuses)
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
            'payload' => $payload,
            'window' => $window,
            'monthlySales' => $monthlySales,
            'bestSellingProducts' => $bestSellingProducts,
            'lowStockProducts' => $lowStockProducts,
            'totalUsers' => User::count(),
            'totalProducts' => Product::count(),
            'totalCategories' => Category::count(),
        ]);
    }

    public function owner(Request $request, AnalyticsService $analytics): View|StreamedResponse
    {
        $window = WindowResolver::resolve($request);
        $ownerId = (int) Auth::id();

        $payload = $this->cachedOverview(
            $analytics,
            'owner',
            $ownerId,
            $window['from'],
            $window['to'],
            $window['granularity'],
            $window['compare'],
        );

        if ($request->input('export') === 'csv') {
            return AnalyticsCsv::stream($payload);
        }

        $salesStatuses = AnalyticsService::SALES_STATUSES;

        $salesItems = OrderIdem::query()
            ->whereHas('product', function ($query) use ($ownerId) {
                $query->where('user_id', $ownerId);
            })
            ->whereHas('order', function ($query) use ($window) {
                $query->whereBetween('created_at', [
                    $window['from']->copy()->startOfDay(),
                    $window['to']->copy()->endOfDay(),
                ]);
            })
            ->whereIn('status', $salesStatuses);

        $bestSellingProducts = (clone $salesItems)
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
            'payload' => $payload,
            'window' => $window,
            'bestSellingProducts' => $bestSellingProducts,
            'lowStockProducts' => $lowStockProducts,
            'inventoryTransactions' => $inventoryTransactions,
        ]);
    }

    /**
     * The analytics payload is pure read-model output, so a short-lived
     * cache keeps repeated filter combinations cheap without staleness
     * leaking between scopes or parameter sets.
     */
    private function cachedOverview(
        AnalyticsService $analytics,
        string $scope,
        ?int $ownerId,
        Carbon $from,
        Carbon $to,
        string $granularity,
        bool $compare,
    ): array {
        $key = sprintf(
            'analytics:%s:%s:%s:%s:%s:%s',
            $scope,
            $ownerId ?? 'all',
            $from->toDateString(),
            $to->toDateString(),
            $granularity,
            $compare ? 'compare' : 'raw',
        );

        return Cache::remember($key, now()->addMinutes(10), fn (): array => $analytics->overview(
            $ownerId,
            $from,
            $to,
            $granularity,
            $compare,
        ));
    }
}
