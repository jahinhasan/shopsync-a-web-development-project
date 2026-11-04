<?php

namespace App\Services\Analytics;

use App\Models\Order;
use App\Models\ShopApplication;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes the ShopSync analytics payload from real database records.
 *
 * Data-integrity rules enforced here:
 * - Net sales always aggregate order items whose status is processing,
 *   shipped or delivered (the authoritative lifecycle), never orders.status.
 * - Cancelled items contribute zero sales; the status distribution shows
 *   them separately.
 * - Statistical techniques are guarded: no regression below 3 points, no
 *   z-score flags below 10 days, no projection below 14 days, no weekday x
 *   hour heatmap below 14 days.
 * - Percentage deltas return null instead of an invented value when the
 *   previous period is zero.
 * - Voucher comparisons are descriptive only; the views must not claim
 *   causation.
 */
class AnalyticsService
{
    public const SALES_STATUSES = ['processing', 'shipped', 'delivered'];

    public const GRANULARITIES = ['daily', 'weekly', 'monthly'];

    /** @var array<int, string> */
    private array $productNames = [];

    private const ANOMALY_MIN_POINTS = 10;

    private const PROJECTION_MIN_POINTS = 14;

    private const HEATMAP_MIN_DAYS = 14;

    /**
     * Build the full analytics payload for the admin (ownerId null) or a
     * single shop owner.
     *
     * @param  int|null  $ownerId  null scopes to the whole platform (admin); an id scopes every query to one owner's products.
     * @param  string  $granularity  daily|weekly|monthly
     * @param  bool  $compare  include the previous-period overlay and delta chips
     * @return array<string, mixed> shape documented per method below
     */
    public function overview(?int $ownerId, Carbon $from, Carbon $to, string $granularity, bool $compare): array
    {
        if (! in_array($granularity, self::GRANULARITIES, true)) {
            $granularity = 'daily';
        }

        // Very long ranges collapse to monthly buckets to keep the series
        // (and the chart) readable.
        if ($granularity === 'daily' && $from->diffInDays($to) > 366) {
            $granularity = 'monthly';
        }

        [$prevFrom, $prevTo] = $this->previousWindow($from, $to);
        $windowDays = (int) $from->diffInDays($to) + 1;

        $series = $this->salesSeries($ownerId, $from, $to, $granularity);
        $totals = $this->totals($ownerId, $from, $to);

        $payload = [
            'window' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'granularity' => $granularity,
                'days' => $windowDays,
            ],
            'totals' => $totals,
            'series' => $series,
            'previous' => null,
            'deltas' => null,
            'orderHistogram' => $this->orderHistogram($ownerId, $from, $to),
            'topProducts' => $this->topProducts($ownerId, $from, $to, $totals['netSales']),
            'categoryPerformance' => $this->categoryPerformance($ownerId, $from, $to),
            'statusDistribution' => $this->statusDistribution($ownerId, $from, $to),
            'acquisition' => $this->acquisition($ownerId, $from, $to, $granularity),
            'vouchers' => $this->voucherPerformance($ownerId, $from, $to),
            'payments' => $this->paymentPerformance($ownerId, $from, $to),
            'inventoryRisk' => $this->inventoryRisk($ownerId, $from, $to, $windowDays),
            'productMovers' => $this->productMovers($ownerId, $from, $to, $prevFrom, $prevTo),
            'heatmap' => $this->orderHeatmap($ownerId, $from, $to, $windowDays),
            'shopPerformance' => $ownerId === null ? $this->shopPerformance($from, $to, $totals['netSales']) : null,
        ];

        if ($compare) {
            $previousTotals = $this->totals($ownerId, $prevFrom, $prevTo);
            $previousSeries = $this->salesSeries($ownerId, $prevFrom, $prevTo, $granularity);

            $payload['previous'] = [
                'window' => ['from' => $prevFrom->toDateString(), 'to' => $prevTo->toDateString()],
                'totals' => $previousTotals,
                'netSales' => $this->alignToCurrent($previousSeries['netSales'], count($series['buckets'])),
                'hasData' => $previousTotals['orders'] > 0 || $previousTotals['netSales'] > 0,
            ];

            $payload['deltas'] = [
                'netSales' => AnalyticsMath::percentageChange((float) $totals['netSales'], (float) $previousTotals['netSales']),
                'orders' => AnalyticsMath::percentageChange((float) $totals['orders'], (float) $previousTotals['orders']),
                'aov' => AnalyticsMath::percentageChange((float) $totals['aov'], (float) $previousTotals['aov']),
                'units' => AnalyticsMath::percentageChange((float) $totals['units'], (float) $previousTotals['units']),
            ];
        }

        return $payload;
    }

    /**
     * Previous window: the immediately preceding stretch of identical length.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    public function previousWindow(Carbon $from, Carbon $to): array
    {
        $length = (int) $from->diffInDays($to) + 1;

        $prevTo = $from->copy()->subDay();

        return [$prevTo->copy()->subDays($length - 1)->startOfDay(), $prevTo->endOfDay()];
    }

    /**
     * Bucketed net sales / units / order counts with every gap filled at zero.
     *
     * @return array{buckets: list<string>, labels: list<string>, netSales: list<float>, units: list<int>, orders: list<int>}
     */
    public function salesSeries(?int $ownerId, Carbon $from, Carbon $to, string $granularity): array
    {
        $bucketExpression = $this->bucketExpression($granularity, 'orders');

        $rows = DB::table('order_idems')
            ->join('orders', 'orders.id', '=', 'order_idems.order_id')
            ->when($ownerId !== null, function ($query) use ($ownerId): void {
                $query->join('products', 'products.id', '=', 'order_idems.product_id')
                    ->where('products.user_id', $ownerId);
            })
            ->whereIn('order_idems.status', self::SALES_STATUSES)
            ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw("{$bucketExpression} as bucket, SUM(order_idems.subtotal) as sales, SUM(order_idems.quantity) as units, COUNT(DISTINCT orders.id) as orders_count")
            ->groupByRaw($bucketExpression)
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        $buckets = $this->bucketKeys($from, $to, $granularity);

        $netSales = [];
        $units = [];
        $orders = [];

        foreach ($buckets as $bucket) {
            $row = $rows->get($bucket['key']);

            $netSales[] = $row !== null ? (float) $row->sales : 0.0;
            $units[] = $row !== null ? (int) $row->units : 0;
            $orders[] = $row !== null ? (int) $row->orders_count : 0;
        }

        return [
            'buckets' => array_column($buckets, 'key'),
            'labels' => array_column($buckets, 'label'),
            'netSales' => $netSales,
            'units' => $units,
            'orders' => $orders,
            'cumulative' => AnalyticsMath::cumulative($netSales),
            'movingAverage' => AnalyticsMath::movingAverage(
                $netSales,
                $granularity === 'daily' ? 7 : 3,
            ),
            'regression' => AnalyticsMath::linearRegression($netSales),
            'projection' => $this->projection($netSales, $granularity),
            'anomalies' => count($netSales) >= 10 ? AnalyticsMath::zScores($netSales) : null,
            'acceleration' => $this->acceleration($netSales),
        ];
    }

    /**
     * Window totals with the platform-wide net-sales definition.
     *
     * @return array{netSales: float, orders: int, units: int, customers: int, aov: float, discountOnSales: float, discountOnOrders: float}
     */
    public function totals(?int $ownerId, Carbon $from, Carbon $to): array
    {
        $items = $this->salesItemQuery($ownerId, $from, $to);

        $netSales = (float) (clone $items)->sum('order_idems.subtotal');
        $units = (int) (clone $items)->sum('order_idems.quantity');
        $discountOnSales = (float) (clone $items)->sum('order_idems.voucher_discount_amount');
        $orders = (int) (clone $items)->distinct()->count('order_idems.order_id');
        $customers = (int) (clone $items)->distinct()->count('orders.user_id');

        // Whole-order voucher discounts on the customer-facing totals for
        // orders that produced net sales in this window.
        $discountOnOrders = (float) (clone $items)
            ->selectRaw('COALESCE(SUM(orders.voucher_discount_total), 0) as discount')
            ->groupBy('order_idems.order_id')
            ->get()
            ->sum('discount');

        return [
            'netSales' => $netSales,
            'orders' => $orders,
            'units' => $units,
            'customers' => $customers,
            'aov' => $orders > 0 ? $netSales / $orders : 0.0,
            'discountOnSales' => $discountOnSales,
            'discountOnOrders' => $discountOnOrders,
        ];
    }

    /**
     * Distribution of order values with mean/median/P90 and equal-width bins.
     * Admin uses the discounted customer total; owners see the revenue of
     * their own items per order.
     *
     * @return array{values: list<float>, mean: float|null, median: float|null, p90: float|null, bins: list<array{from: float, to: float, count: int}>}|null
     */
    public function orderHistogram(?int $ownerId, Carbon $from, Carbon $to): ?array
    {
        $rows = DB::table('orders')
            ->join('order_idems', 'order_idems.order_id', '=', 'orders.id')
            ->when($ownerId !== null, function ($query) use ($ownerId): void {
                $query->join('products', 'products.id', '=', 'order_idems.product_id')
                    ->where('products.user_id', $ownerId);
            })
            ->whereIn('order_idems.status', self::SALES_STATUSES)
            ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('orders.id');

        $values = $ownerId !== null
            ? $rows->selectRaw('COALESCE(SUM(order_idems.subtotal), 0) as value')->pluck('value')
            : $rows->selectRaw('MAX(orders.total) as value')->pluck('value');

        $values = array_map('floatval', $values->all());

        if ($values === []) {
            return null;
        }

        $min = min($values);
        $max = max($values);
        $binCount = 6;

        $bins = [];

        if ($max - $min < 0.01) {
            $bins[] = ['from' => floor($min), 'to' => ceil($max), 'count' => count($values)];
        } else {
            $width = ($max - $min) / $binCount;

            for ($i = 0; $i < $binCount; $i++) {
                $binFrom = $min + ($width * $i);
                $binTo = $min + ($width * ($i + 1));

                $bins[] = [
                    'from' => $binFrom,
                    'to' => $binTo,
                    'count' => count(array_filter(
                        $values,
                        fn (float $value): bool => $value >= $binFrom && ($i === $binCount - 1 ? $value <= $binTo : $value < $binTo),
                    )),
                ];
            }
        }

        return [
            'values' => $values,
            'mean' => AnalyticsMath::mean($values),
            'median' => AnalyticsMath::median($values),
            'p90' => AnalyticsMath::percentile($values, 90),
            'bins' => $bins,
        ];
    }

    /**
     * Pareto view of the best-selling products: revenue share and cumulative
     * share over the period's net sales.
     *
     * @return list<{product_id: int, name: string, units: int, revenue: float, share: float, cumulative: float}>
     */
    public function topProducts(?int $ownerId, Carbon $from, Carbon $to, float $netSales): array
    {
        $rows = $this->salesItemQuery($ownerId, $from, $to)
            ->selectRaw('order_idems.product_id, MAX(order_idems.product_name) as name, SUM(order_idems.quantity) as units, SUM(order_idems.subtotal) as revenue')
            ->groupBy('order_idems.product_id')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $cumulative = 0.0;

        return $rows->map(function ($row) use ($netSales, &$cumulative): array {
            $share = $netSales > 0 ? ((float) $row->revenue / $netSales) * 100 : 0.0;
            $cumulative += $share;

            return [
                'product_id' => (int) $row->product_id,
                'name' => (string) $row->name,
                'units' => (int) $row->units,
                'revenue' => (float) $row->revenue,
                'share' => round($share, 1),
                'cumulative' => round($cumulative, 1),
            ];
        })->all();
    }

    /**
     * Revenue and units per category (uncategorised products grouped last).
     *
     * @return list<{name: string, units: int, revenue: float, share: float}>
     */
    public function categoryPerformance(?int $ownerId, Carbon $from, Carbon $to): array
    {
        $rows = $this->salesItemQuery($ownerId, $from, $to)
            // Aliased: itemQuery already joins products for owner scope.
            ->leftJoin('products as category_products', 'category_products.id', '=', 'order_idems.product_id')
            ->leftJoin('categories', 'categories.id', '=', 'category_products.category_id')
            ->selectRaw("COALESCE(categories.name, 'Uncategorised') as name, SUM(order_idems.quantity) as units, SUM(order_idems.subtotal) as revenue")
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->get();

        $total = (float) $rows->sum('revenue');

        return $rows->map(function ($row) use ($total): array {
            return [
                'name' => (string) $row->name,
                'units' => (int) $row->units,
                'revenue' => (float) $row->revenue,
                'share' => $total > 0 ? round(((float) $row->revenue / $total) * 100, 1) : 0.0,
            ];
        })->all();
    }

    /**
     * Item-status counts for the window — cancelled items included so the
     * funnel shows cancellations instead of hiding them.
     *
     * @return list<{status: string, count: int, share: float}>
     */
    public function statusDistribution(?int $ownerId, Carbon $from, Carbon $to): array
    {
        $rows = $this->itemQuery($ownerId, $from, $to)
            ->selectRaw('order_idems.status, COUNT(*) as count')
            ->groupBy('order_idems.status')
            ->orderByDesc('count')
            ->get();

        $total = (int) $rows->sum('count');

        return $rows->map(function ($row) use ($total): array {
            return [
                'status' => (string) $row->status,
                'count' => (int) $row->count,
                'share' => $total > 0 ? round(((int) $row->count / $total) * 100, 1) : 0.0,
            ];
        })->all();
    }

    /**
     * Customer growth per bucket plus the repeat-purchase split. Admins see
     * new platform signups; owners see the distinct customers ordering from
     * their shop each bucket.
     *
     * @return array{metric: string, buckets: list<string>, labels: list<string>, customers: list<int>, orderingCustomers: int, repeatCustomers: int, repeatRate: float|null}
     */
    public function acquisition(?int $ownerId, Carbon $from, Carbon $to, string $granularity): array
    {
        $buckets = $this->bucketKeys($from, $to, $granularity);

        if ($ownerId !== null) {
            $bucketExpression = $this->bucketExpression($granularity, 'orders');

            $perBucket = DB::table('order_idems')
                ->join('orders', 'orders.id', '=', 'order_idems.order_id')
                ->join('products', 'products.id', '=', 'order_idems.product_id')
                ->where('products.user_id', $ownerId)
                ->whereIn('order_idems.status', self::SALES_STATUSES)
                ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->selectRaw("{$bucketExpression} as bucket, COUNT(DISTINCT orders.user_id) as count")
                ->groupByRaw($bucketExpression)
                ->orderBy('bucket')
                ->get()
                ->keyBy('bucket');

            $customers = [];

            foreach ($buckets as $bucket) {
                $row = $perBucket->get($bucket['key']);
                $customers[] = $row !== null ? (int) $row->count : 0;
            }

            $metric = 'ordering';
        } else {
            $bucketExpression = $this->bucketExpression($granularity, 'users');

            $signupRows = DB::table('users')
                ->where('role', 'customer')
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->selectRaw("{$bucketExpression} as bucket, COUNT(*) as count")
                ->groupByRaw($bucketExpression)
                ->orderBy('bucket')
                ->get()
                ->keyBy('bucket');

            $customers = [];

            foreach ($buckets as $bucket) {
                $row = $signupRows->get($bucket['key']);
                $customers[] = $row !== null ? (int) $row->count : 0;
            }

            $metric = 'signups';
        }

        // Repeat split over orders that produced net sales in the window.
        $perCustomer = $this->salesItemQuery($ownerId, $from, $to)
            ->selectRaw('orders.user_id, COUNT(DISTINCT order_idems.order_id) as order_count')
            ->groupBy('orders.user_id')
            ->get();

        $orderingCustomers = $perCustomer->count();
        $repeatCustomers = $perCustomer->filter(fn ($row): bool => (int) $row->order_count >= 2)->count();

        return [
            'metric' => $metric,
            'buckets' => array_column($buckets, 'key'),
            'labels' => array_column($buckets, 'label'),
            'customers' => $customers,
            'orderingCustomers' => $orderingCustomers,
            'repeatCustomers' => $repeatCustomers,
            'repeatRate' => $orderingCustomers > 0 ? round(($repeatCustomers / $orderingCustomers) * 100, 1) : null,
        ];
    }

    /**
     * Voucher funnel and in-window usage. Descriptive only: redemption rates
     * and revenue splits never imply that vouchers caused the revenue.
     *
     * @return array{issued: int, redeemed: int, redemptionRate: float|null, usage: list<array{code: string, orders: int, discount: float}>, salesWithVoucher: float, salesWithoutVoucher: float}
     */
    public function voucherPerformance(?int $ownerId, Carbon $from, Carbon $to): array
    {
        $funnel = DB::table('customer_vouchers')
            ->selectRaw("COUNT(*) as issued, COALESCE(SUM(CASE WHEN customer_vouchers.status = 'redeemed' THEN 1 ELSE 0 END), 0) as redeemed")
            ->first();

        // Discount lives on the order, so each voucher order is aggregated
        // once before grouping by code — summing over joined item rows
        // would multiply an order-level discount by its item count.
        $voucherOrders = DB::table('orders')
            ->join('order_idems', 'order_idems.order_id', '=', 'orders.id')
            ->when($ownerId !== null, function ($query) use ($ownerId): void {
                $query->join('products', 'products.id', '=', 'order_idems.product_id')
                    ->where('products.user_id', $ownerId);
            })
            ->whereIn('order_idems.status', self::SALES_STATUSES)
            ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->whereNotNull('orders.voucher_code')
            ->where('orders.voucher_code', '!=', '')
            ->groupBy('orders.id')
            ->get(['orders.id', 'orders.voucher_code', 'orders.voucher_discount_total']);

        $usageByCode = [];

        foreach ($voucherOrders as $order) {
            $usageByCode[$order->voucher_code]['orders'] = ($usageByCode[$order->voucher_code]['orders'] ?? 0) + 1;
            $usageByCode[$order->voucher_code]['discount'] = ($usageByCode[$order->voucher_code]['discount'] ?? 0.0) + (float) $order->voucher_discount_total;
        }

        uasort($usageByCode, fn (array $a, array $b): int => $b['discount'] <=> $a['discount']);

        $usage = collect($usageByCode)
            ->take(10)
            ->map(fn (array $row, string $code): array => [
                'code' => $code,
                'orders' => $row['orders'],
                'discount' => round($row['discount'], 2),
            ])->values();

        $base = $this->salesItemQuery($ownerId, $from, $to);

        $salesWithVoucher = (float) (clone $base)
            ->whereNotNull('orders.voucher_code')
            ->where('orders.voucher_code', '!=', '')
            ->sum('order_idems.subtotal');

        $salesWithoutVoucher = (float) (clone $base)
            ->where(function ($query): void {
                $query->whereNull('orders.voucher_code')->orWhere('orders.voucher_code', '');
            })
            ->sum('order_idems.subtotal');

        $issued = (int) ($funnel->issued ?? 0);
        $redeemed = (int) ($funnel->redeemed ?? 0);

        return [
            'issued' => $issued,
            'redeemed' => $redeemed,
            'redemptionRate' => $issued > 0 ? round(($redeemed / $issued) * 100, 1) : null,
            'usage' => $usage->map(fn ($row): array => [
                'code' => (string) $row->code,
                'orders' => (int) $row->orders,
                'discount' => (float) $row->discount,
            ])->all(),
            'salesWithVoucher' => $salesWithVoucher,
            'salesWithoutVoucher' => $salesWithoutVoucher,
        ];
    }

    /**
     * Payment-method mix for orders in the window (owner-scoped to orders
     * containing the owner's products when set) plus the median customer
     * payment-submission latency. Verification turnaround is not measured:
     * the schema records when a customer submits a mobile-money reference
     * but never when the owner verifies it.
     *
     * @return array{mix: list<{method: string, count: int, share: float}>, submissionMedianSeconds: int|null, submissionSamples: int}
     */
    public function paymentPerformance(?int $ownerId, Carbon $from, Carbon $to): array
    {
        $orders = Order::query()
            ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($ownerId !== null, function ($query) use ($ownerId): void {
                $query->whereHas('items.product', fn ($product) => $product->where('products.user_id', $ownerId));
            });

        $rows = (clone $orders)
            ->selectRaw("COALESCE(orders.payment_method, 'unspecified') as method, COUNT(*) as count")
            ->groupBy('orders.payment_method')
            ->orderByDesc('count')
            ->get();

        $total = (int) $rows->sum('count');

        $latencies = (clone $orders)
            ->whereNotNull('orders.payment_submitted_at')
            ->get(['orders.created_at', 'orders.payment_submitted_at'])
            ->map(fn ($row): int => max(0, (int) Carbon::parse($row->payment_submitted_at)->diffInSeconds(Carbon::parse($row->created_at))))
            ->all();

        $median = AnalyticsMath::median($latencies);

        return [
            'mix' => $rows->map(function ($row) use ($total): array {
                return [
                    'method' => (string) $row->method,
                    'count' => (int) $row->count,
                    'share' => $total > 0 ? round(((int) $row->count / $total) * 100, 1) : 0.0,
                ];
            })->all(),
            'submissionMedianSeconds' => $median !== null ? (int) round($median) : null,
            'submissionSamples' => count($latencies),
        ];
    }

    /**
     * Stock burn-down from the inventory ledger: consumption per day in the
     * window and how many days of stock remain at that rate.
     *
     * @return list<{product_id: int, name: string, stock: int, soldUnits: int, burnPerDay: float, daysOfStock: float|null, restocks: int}>
     */
    public function inventoryRisk(?int $ownerId, Carbon $from, Carbon $to, int $windowDays): array
    {
        $soldRows = DB::table('products')
            ->join('inventory_transactions as it', function ($join): void {
                $join->on('it.product_id', '=', 'products.id')->where('it.type', 'sale');
            })
            ->whereBetween('it.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($ownerId !== null, fn ($query) => $query->where('products.user_id', $ownerId))
            ->selectRaw('products.id, MAX(products.name) as name, products.stock, SUM(ABS(it.quantity_change)) as sold_units')
            ->groupBy('products.id', 'products.stock')
            ->orderByDesc('sold_units')
            ->get();

        $restockCounts = DB::table('inventory_transactions')
            ->where('inventory_transactions.type', 'restock')
            ->whereBetween('inventory_transactions.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->when($ownerId !== null, function ($query) use ($ownerId): void {
                $query->join('products', 'products.id', '=', 'inventory_transactions.product_id')
                    ->where('products.user_id', $ownerId);
            })
            ->selectRaw('inventory_transactions.product_id, COUNT(*) as restocks')
            ->groupBy('inventory_transactions.product_id')
            ->pluck('restocks', 'product_id');

        return $soldRows->map(function ($row) use ($windowDays, $restockCounts): array {
            $burnPerDay = (float) $row->sold_units / max(1, $windowDays);
            $stock = (int) $row->stock;

            return [
                'product_id' => (int) $row->id,
                'name' => (string) $row->name,
                'stock' => $stock,
                'soldUnits' => (int) $row->sold_units,
                'burnPerDay' => round($burnPerDay, 2),
                'daysOfStock' => $burnPerDay > 0 ? round($stock / $burnPerDay, 1) : null,
                'restocks' => (int) ($restockCounts[$row->id] ?? 0),
            ];
        })
            ->sortBy(fn (array $row): float => $row['daysOfStock'] ?? PHP_FLOAT_MAX)
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * Biggest per-product revenue movers versus the previous period.
     *
     * @return array{risers: list<array{name: string, revenue: float, previous: float, change: float}>, decliners: list<array{name: string, revenue: float, previous: float, change: float}>, newProducts: int}
     */
    public function productMovers(?int $ownerId, Carbon $from, Carbon $to, Carbon $prevFrom, Carbon $prevTo): array
    {
        $current = $this->revenueByProduct($ownerId, $from, $to);
        $previous = $this->revenueByProduct($ownerId, $prevFrom, $prevTo);

        $risers = [];
        $decliners = [];
        $newProducts = 0;

        foreach ($current as $productId => $revenue) {
            $name = $this->productNames[$productId] ?? "#{$productId}";
            $prevRevenue = $previous[$productId] ?? 0.0;

            if ($prevRevenue <= 0) {
                if ($revenue > 0) {
                    $newProducts++;
                }

                continue;
            }

            $change = (($revenue - $prevRevenue) / $prevRevenue) * 100;

            $entry = ['name' => $name, 'revenue' => $revenue, 'previous' => $prevRevenue, 'change' => round($change, 1)];

            if ($change > 0) {
                $risers[] = $entry;
            } elseif ($change < 0) {
                $decliners[] = $entry;
            }
        }

        // Products that sold last period but not at all in this one are the
        // steepest decliners the window can show (-100%).
        foreach ($previous as $productId => $prevRevenue) {
            if (! isset($current[$productId]) && $prevRevenue > 0) {
                $decliners[] = [
                    'name' => $this->productNames[$productId] ?? "#{$productId}",
                    'revenue' => 0.0,
                    'previous' => $prevRevenue,
                    'change' => -100.0,
                ];
            }
        }

        usort($risers, fn (array $a, array $b): int => $b['change'] <=> $a['change']);
        usort($decliners, fn (array $a, array $b): int => $a['change'] <=> $b['change']);

        return [
            'risers' => array_slice($risers, 0, 5),
            'decliners' => array_slice($decliners, 0, 5),
            'newProducts' => $newProducts,
        ];
    }

    /**
     * Orders by weekday x two-hour slot. Needs at least two weeks of data
     * before a rhythm claim is honest.
     *
     * @return array{cells: list<list<int>>, max: int}|null
     */
    public function orderHeatmap(?int $ownerId, Carbon $from, Carbon $to, int $windowDays): ?array
    {
        if ($windowDays < self::HEATMAP_MIN_DAYS) {
            return null;
        }

        $isSqlite = DB::getDriverName() === 'sqlite';

        $dayExpression = $isSqlite ? "strftime('%w', orders.created_at)" : '(DAYOFWEEK(orders.created_at) - 1)';
        $slotExpression = $isSqlite
            ? "(CAST(strftime('%H', orders.created_at) AS INTEGER) / 2)"
            : '(HOUR(orders.created_at) DIV 2)';

        $rows = DB::table('orders')
            ->join('order_idems', 'order_idems.order_id', '=', 'orders.id')
            ->when($ownerId !== null, function ($query) use ($ownerId): void {
                $query->join('products', 'products.id', '=', 'order_idems.product_id')
                    ->where('products.user_id', $ownerId);
            })
            ->whereIn('order_idems.status', self::SALES_STATUSES)
            ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw("{$dayExpression} as day, {$slotExpression} as slot, COUNT(DISTINCT orders.id) as count")
            ->groupByRaw("{$dayExpression}, {$slotExpression}")
            ->get();

        $cells = array_fill(0, 7, array_fill(0, 12, 0));
        $max = 0;

        foreach ($rows as $row) {
            $day = AnalyticsMath::weekdayFromDriverValue((int) $row->day);
            $slot = min(11, max(0, (int) $row->slot));
            $cells[$day][$slot] = (int) $row->count;
            $max = max($max, (int) $row->count);
        }

        return ['cells' => $cells, 'max' => $max];
    }

    /**
     * Net sales per approved shop (admin only). Names come from approved
     * shop applications, falling back to the account name.
     *
     * @return list<array{owner_id: int, name: string, revenue: float, orders: int, share: float}>
     */
    public function shopPerformance(Carbon $from, Carbon $to, float $netSales): array
    {
        $rows = DB::table('order_idems')
            ->join('orders', 'orders.id', '=', 'order_idems.order_id')
            ->join('products', 'products.id', '=', 'order_idems.product_id')
            ->whereIn('order_idems.status', self::SALES_STATUSES)
            ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('products.user_id, SUM(order_idems.subtotal) as revenue, COUNT(DISTINCT orders.id) as orders_count')
            ->groupBy('products.user_id')
            ->orderByDesc('revenue')
            ->get();

        $ownerIds = $rows->pluck('user_id')->all();

        $shopNames = ShopApplication::query()
            ->where('status', 'approved')
            ->whereIn('user_id', $ownerIds)
            ->pluck('shop_name', 'user_id');

        $accountNames = User::query()
            ->whereIn('id', $ownerIds)
            ->pluck('name', 'id');

        return $rows->map(function ($row) use ($netSales, $shopNames, $accountNames): array {
            return [
                'owner_id' => (int) $row->user_id,
                'name' => $shopNames[$row->user_id] ?? $accountNames[$row->user_id] ?? "Shop #{$row->user_id}",
                'revenue' => (float) $row->revenue,
                'orders' => (int) $row->orders_count,
                'share' => $netSales > 0 ? round(((float) $row->revenue / $netSales) * 100, 1) : 0.0,
            ];
        })->all();
    }

    /**
     * Extend the fitted trend past the window when there is enough history.
     * Projections are labelled and rendered dashed by the views — never
     * merged into the historical series.
     *
     * @param  list<float>  $values
     * @return array{buckets: int, values: list<float>}|null
     */
    private function projection(array $values, string $granularity): ?array
    {
        $regression = AnalyticsMath::linearRegression($values);

        if ($regression === null || count($values) < self::PROJECTION_MIN_POINTS) {
            return null;
        }

        $buckets = match ($granularity) {
            'weekly' => 2,
            'monthly' => 1,
            default => 7,
        };

        $projected = [];

        foreach (range(count($values), count($values) + $buckets - 1) as $x) {
            $projected[] = round(($regression['slope'] * $x) + $regression['intercept'], 2);
        }

        return ['buckets' => $buckets, 'values' => $projected];
    }

    /**
     * Rate-of-change of the trend: recent slope minus prior slope over daily
     * windows, in ৳/day². Needs two full weeks.
     *
     * @param  list<float>  $values
     * @return array{delta: float, direction: string}|null
     */
    private function acceleration(array $values): ?array
    {
        if (count($values) < 14) {
            return null;
        }

        $recentFit = AnalyticsMath::linearRegression(array_slice($values, -7));
        $priorFit = AnalyticsMath::linearRegression(array_slice($values, -14, 7));

        $recentSlope = $recentFit['slope'] ?? null;
        $priorSlope = $priorFit['slope'] ?? null;

        if ($recentSlope === null || $priorSlope === null) {
            return null;
        }

        $delta = $recentSlope - $priorSlope;
        $mean = AnalyticsMath::mean($values) ?? 0.0;
        $threshold = max(1.0, abs($mean) * 0.01);

        $direction = match (true) {
            $delta > $threshold => 'accelerating',
            $delta < -$threshold => 'decelerating',
            default => 'steady',
        };

        return ['delta' => round($delta, 2), 'direction' => $direction];
    }

    /**
     * Driver-aware bucket key expression. Weekly buckets key on the Monday
     * that starts the week on both MySQL/TiDB and SQLite so labels agree.
     */
    private function bucketExpression(string $granularity, string $table): string
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        return match ($granularity) {
            'weekly' => $isSqlite
                ? "DATE({$table}.created_at, 'weekday 0', '-6 days')"
                : "DATE_SUB(DATE({$table}.created_at), INTERVAL WEEKDAY({$table}.created_at) DAY)",
            'monthly' => $isSqlite
                ? "strftime('%Y-%m', {$table}.created_at)"
                : "DATE_FORMAT({$table}.created_at, '%Y-%m')",
            default => "DATE({$table}.created_at)",
        };
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function bucketKeys(Carbon $from, Carbon $to, string $granularity): array
    {
        $buckets = [];

        if ($granularity === 'monthly') {
            $cursor = $from->copy()->startOfMonth();

            while ($cursor->lessThanOrEqualTo($to)) {
                $buckets[] = ['key' => $cursor->format('Y-m'), 'label' => $cursor->format('M Y')];

                $cursor->addMonth();
            }

            return $buckets;
        }

        if ($granularity === 'weekly') {
            $cursor = $from->copy()->startOfWeek(Carbon::MONDAY);

            while ($cursor->lessThanOrEqualTo($to)) {
                $buckets[] = ['key' => $cursor->toDateString(), 'label' => 'Wk '.$cursor->format('j M')];

                $cursor->addWeek();
            }

            return $buckets;
        }

        $cursor = $from->copy()->startOfDay();

        while ($cursor->lessThanOrEqualTo($to)) {
            $buckets[] = ['key' => $cursor->toDateString(), 'label' => $cursor->format('j M')];

            $cursor->addDay();
        }

        return $buckets;
    }

    /**
     * Base query for net-sales items in a window (owner-scoped when set).
     */
    private function salesItemQuery(?int $ownerId, Carbon $from, Carbon $to)
    {
        return $this->itemQuery($ownerId, $from, $to)
            ->whereIn('order_idems.status', self::SALES_STATUSES);
    }

    /**
     * Base query for all order items in a window regardless of status.
     */
    private function itemQuery(?int $ownerId, Carbon $from, Carbon $to)
    {
        return DB::table('order_idems')
            ->join('orders', 'orders.id', '=', 'order_idems.order_id')
            ->when($ownerId !== null, function ($query) use ($ownerId): void {
                $query->join('products', 'products.id', '=', 'order_idems.product_id')
                    ->where('products.user_id', $ownerId);
            })
            ->whereBetween('orders.created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);
    }

    /**
     * Align a previous-period series to the current bucket count so the two
     * can share one axis (same measure, same scale).
     *
     * @param  list<float>  $values
     * @return list<float|null>
     */
    private function alignToCurrent(array $values, int $length): array
    {
        $values = array_values($values);

        if (count($values) >= $length) {
            return array_slice($values, 0, $length);
        }

        return array_pad($values, $length, null);
    }

    /**
     * @return array<int, float> product_id => revenue
     */
    private function revenueByProduct(?int $ownerId, Carbon $from, Carbon $to): array
    {
        $rows = $this->salesItemQuery($ownerId, $from, $to)
            ->selectRaw('order_idems.product_id, SUM(order_idems.subtotal) as revenue')
            ->groupBy('order_idems.product_id')
            ->get();

        $this->loadProductNames($rows->pluck('product_id')->all());

        return $rows
            ->mapWithKeys(fn ($row): array => [(int) $row->product_id => (float) $row->revenue])
            ->all();
    }

    private function loadProductNames(array $productIds): void
    {
        $missing = array_diff($productIds, array_keys($this->productNames));

        if ($missing === []) {
            return;
        }

        DB::table('products')
            ->whereIn('id', $missing)
            ->select('id', 'name')
            ->get()
            ->each(function ($row): void {
                $this->productNames[$row->id] = $row->name;
            });
    }
}
