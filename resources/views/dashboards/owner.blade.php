<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">
                    Business Workspace
                </p>
                <h2 class="mt-1 text-xl font-bold text-gray-900">
                    Owner Dashboard
                </h2>
            </div>

            <a href="{{ route('owner.reports') }}"
               class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                View Reports →
            </a>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-50/80 py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">

            {{-- Welcome --}}
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-700 via-indigo-600 to-violet-600 p-6 text-white shadow-lg sm:p-8"
                     data-reveal>
                <div class="relative z-10 flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-sm font-semibold text-indigo-100">
                            Your store at a glance
                        </p>

                        <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">
                            Welcome, {{ auth()->user()->name }}
                        </h1>

                        <p class="mt-3 max-w-xl text-sm leading-6 text-indigo-100 sm:text-base">
                            Monitor your sales, manage inventory and keep customer orders moving.
                        </p>
                    </div>

                    <a href="{{ route('owner.products.create') }}"
                       class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-bold text-indigo-700 shadow-sm transition hover:-translate-y-0.5 hover:bg-indigo-50">
                        + Add Product
                    </a>
                </div>

                <div class="pointer-events-none absolute -right-12 -top-20 h-64 w-64 rounded-full border-[32px] border-white/10"></div>
                <div class="pointer-events-none absolute -bottom-24 right-40 h-48 w-48 rounded-full bg-white/5"></div>
            </section>

            {{-- Your store in numbers: real totals; delta chips only where a
                 previous-window comparison is meaningful. All data is scoped
                 to the authenticated owner's products. --}}
            <section class="grid gap-4 sm:grid-cols-2">
                <x-kpi-card title="Total Products" :value="number_format($totalProducts)"
                            subtitle="Products in your catalogue" icon="📦"
                            :href="route('owner.products.index')"/>
                <x-kpi-card title="Total Orders" :value="number_format($totalOrders)"
                            subtitle="Orders containing your products" icon="🛍️"
                            :delta="$trend['deltas']['orders'] ?? null"
                            :deltaLabel="'orders vs '.$window['prevLabel']"
                            :href="route('owner.order.manage')"/>
                <x-kpi-card title="Processed Sales" :value="'৳'.number_format((float) $totalSales, 2)"
                            subtitle="Your items in processing, shipped or delivered status" icon="৳"
                            :delta="$trend['deltas']['netSales'] ?? null"
                            :deltaLabel="'sales vs '.$window['prevLabel']"
                            :href="route('owner.order.manage', ['status' => 'sales'])"/>
                <x-kpi-card title="Pending Orders" :value="number_format($pendingOrders)"
                            subtitle="Your items waiting on processing — may need action" icon="⏳"
                            :href="route('owner.order.manage', ['status' => 'pending'])"/>
                <x-kpi-card title="Low-Stock Products" :value="number_format($trend['stockHealth']['lowStock'])"
                            subtitle="Your products with stock ≤ 5 right now" icon="⚠️"
                            :href="route('owner.products.index', ['stock_status' => 'low_stock'])"/>
                <x-kpi-card title="Average Order Value" :value="'৳'.number_format((float) $averageOrderValue, 2)"
                            subtitle="Your net sales ÷ qualifying orders" icon="💠"
                            :delta="$trend['deltas']['aov'] ?? null"
                            :deltaLabel="'AOV vs '.$window['prevLabel']"
                            :href="route('owner.order.manage', ['status' => 'sales'])"/>
            </section>

            {{-- Today summary --}}
            <section class="grid gap-4 sm:grid-cols-3">
                <article class="rounded-2xl border border-gray-200 bg-white p-5" data-reveal>
                    <p class="text-sm font-medium text-gray-500">Today's Sales</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        ৳{{ number_format((float) $todaySales, 2) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Qualifying orders placed today</p>
                </article>

                <article class="rounded-2xl border border-gray-200 bg-white p-5" data-reveal>
                    <p class="text-sm font-medium text-gray-500">Today's Orders</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ number_format($todayOrders) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Orders containing your products</p>
                </article>

                <article class="rounded-2xl border border-gray-200 bg-white p-5" data-reveal>
                    <p class="text-sm font-medium text-gray-500">Available Stock</p>
                    <p class="mt-2 text-2xl font-bold text-gray-900">
                        {{ number_format($totalStock) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">Units across your products</p>
                </article>
            </section>

            {{-- Analytics — scoped to this shop, window-driven, guarded --}}
            @php
                $summary = $trend['series']['summary'];
                $funnel = $trend['orderFunnel'];

                $statusColors = [
                    'delivered' => 'var(--ss-status-good)',
                    'pending' => 'var(--ss-status-warning)',
                    'cancelled' => 'var(--ss-status-critical)',
                    'processing' => '#6b7280',
                    'shipped' => '#475569',
                ];

                $statusSegments = collect($trend['statusDistribution'])
                    ->map(fn (array $row): array => [
                        'label' => ucfirst($row['status']),
                        'value' => $row['count'],
                        'display' => number_format($row['count']).' items',
                        'short' => number_format($row['count']),
                        'color' => $statusColors[$row['status']] ?? '#9ca3af',
                    ])->all();

                $maLabel = $trend['window']['granularity'] === 'daily' ? '7-period average' : '3-period average';
                $bucketNoun = ['daily' => 'Day', 'weekly' => 'Week', 'monthly' => 'Month'][$trend['window']['granularity']];

                $topProducts = collect($trend['topProducts'])->take(5);
                $topProductsShare = $topProducts->count() > 0
                    ? $topProducts->last()['cumulative']
                    : null;
                $paretoFootnote = $topProductsShare !== null
                    ? 'Observed concentration: the top '.$topProducts->count().' selling products account for '.$topProductsShare.'% of '.strtolower($window['label']).' net sales — a measured fact, not an assumed 80/20 rule.'
                    : 'No sales in this window yet.';

                $categories = collect($trend['categoryPerformance'])->take(6);
                $acquisition = $trend['acquisition'];
                $distribution = $acquisition['orderDistribution'];
                $riskRows = collect($trend['inventoryRisk'])->take(5);
                $stock = $trend['stockHealth'];
            @endphp

            <section class="space-y-4" data-reveal>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Your analytics</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ $window['label'] }} · compared with the {{ $window['prevLabel'] }} · your products only · cached for 10 minutes
                        </p>
                    </div>
                    <x-range-picker routeName="owner.dashboard" :active="$window['window']"/>
                </div>

                {{-- Row 1: sales trend + order outcomes --}}
                <div class="grid gap-4 lg:grid-cols-3">
                    <x-chart-card title="Your sales trend" class="lg:col-span-2"
                                  subtitle="Least-squares trend over {{$trend['window']['granularity']}} buckets; the dotted continuation is a projection — an estimate, never a fact."
                                  footnote="Net sales count only your items in processing, shipped or delivered status; cancelled and pending items are excluded. The moving average smooths short-term noise. Regression R² {{ $summary['r2'] !== null ? number_format($summary['r2'], 2).' ('.$summary['fit'].' fit — how closely sales follow a straight line)' : 'unavailable — needs at least 3 buckets of data' }}.">
                        <div class="mb-3 flex flex-wrap items-center gap-2 text-xs">
                            @if ($summary['direction'] !== null)
                                <span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 font-medium text-gray-700">
                                    Trend: {{ $summary['direction'] }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 font-medium text-gray-700">
                                    Rate of change: {{ $summary['rateOfChange'] ?? '—' }}
                                </span>
                                @if (($trend['deltas']['netSales'] ?? null) !== null)
                                    <span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 font-medium text-gray-700 tabular-nums">
                                        {{ \App\Services\Analytics\Formats::signedPercent((float) $trend['deltas']['netSales']) }} vs {{ $window['prevLabel'] }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-1 font-medium text-gray-700">
                                        No {{ $window['prevLabel'] }} to compare
                                    </span>
                                @endif
                            @else
                                <span class="text-gray-500">Not enough history for trend statistics yet.</span>
                            @endif
                        </div>

                        <x-chart-line :labels="$trend['series']['labels']"
                                     :series="[
                                         ['name' => 'Net sales', 'values' => $trend['series']['netSales'], 'color' => 'var(--ss-chart-1)', 'area' => true],
                                         ['name' => $maLabel, 'values' => $trend['series']['movingAverage'], 'color' => 'var(--ss-chart-dim)', 'dashed' => true],
                                     ]"
                                     :regression="$trend['series']['regression']"
                                     :projection="$trend['series']['projection']"
                                     :anomalies="$trend['series']['anomalies']"
                                     format="money"
                                     aria-label="Your shop's net sales trend"/>

                        <x-slot name="table">
                            <div class="max-h-64 overflow-y-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600">{{ $bucketNoun }}</th>
                                            <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Orders</th>
                                            <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Net sales</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 tabular-nums">
                                        @forelse ($trend['series']['labels'] as $index => $label)
                                            <tr>
                                                <td class="px-4 py-2 text-gray-700">{{ $label }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ number_format($trend['series']['orders'][$index]) }}</td>
                                                <td class="px-4 py-2 text-right text-gray-900">৳{{ number_format($trend['series']['netSales'][$index], 2) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">No data yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-slot>
                    </x-chart-card>

                    <div class="space-y-4">
                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <h4 class="text-sm font-bold text-gray-900">Order outcomes</h4>
                            <p class="mt-1 text-xs text-gray-500">Share of all items containing your products.</p>
                            @if ($funnel['total'] > 0)
                                <div class="mt-3 grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p class="text-xs font-medium text-gray-500">Completion rate</p>
                                        <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">{{ $funnel['completionRate'] }}%</p>
                                        <p class="mt-0.5 text-[11px] leading-4 text-gray-500">{{ number_format($funnel['delivered']) }} of {{ number_format($funnel['total']) }} items delivered</p>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p class="text-xs font-medium text-gray-500">Cancellation rate</p>
                                        <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">{{ $funnel['cancellationRate'] }}%</p>
                                        <p class="mt-0.5 text-[11px] leading-4 text-gray-500">{{ number_format($funnel['cancelled']) }} cancelled, {{ number_format($funnel['open']) }} still open</p>
                                    </div>
                                </div>
                            @else
                                <x-empty-state class="mt-3" message="No items in this window yet." hint="Rates appear once orders are placed."/>
                            @endif
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                            <h4 class="text-sm font-bold text-gray-900">Item status mix</h4>
                            <p class="mt-1 text-xs text-gray-500">All items placed in the window, cancelled included.</p>
                            <div class="mt-3">
                                @if (count($statusSegments) > 0)
                                    <x-chart-segmented :items="$statusSegments" variant="thin"
                                                      :show-legend="false"
                                                      aria-label="Your item status distribution"/>
                                @else
                                    <x-empty-state message="No orders yet."/>
                                @endif
                            </div>
                            <ul class="mt-3 space-y-1.5">
                                @foreach ($trend['statusDistribution'] as $row)
                                    <li class="flex items-center justify-between text-xs">
                                        <span class="flex items-center gap-2 text-gray-600">
                                            <span class="h-2 w-2 rounded-sm" style="background: {{ $statusColors[$row['status']] ?? '#9ca3af' }}"
                                                  aria-hidden="true"></span>
                                            {{ ucfirst($row['status']) }}
                                        </span>
                                        <span class="font-medium tabular-nums text-gray-800">
                                            {{ number_format($row['count']) }} <span class="text-gray-400">({{ $row['share'] }}%)</span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Row 2: product performance + your customers --}}
                <div class="grid gap-4 lg:grid-cols-3">
                    <x-chart-card title="Product performance" class="lg:col-span-2"
                                  subtitle="Pareto ranking by net sales; the table twin carries the cumulative share."
                                  footnote="{{ $paretoFootnote }}">
                        @if ($topProducts->count() > 0)
                            <x-chart-rank-bars :items="$topProducts->map(fn ($p) => [
                                'name' => $p['name'],
                                'value' => $p['revenue'],
                                'display' => '৳'.number_format($p['revenue'], 0).' ('.$p['share'].'%)',
                            ])->all()" aria-label="Your top products by revenue"/>
                        @else
                            <x-empty-state message="No product sales in this window yet." hint="Top sellers appear once qualifying orders arrive."/>
                        @endif

                        <x-slot name="table">
                            <div class="max-h-64 overflow-y-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600">#</th>
                                            <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Product</th>
                                            <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Units</th>
                                            <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Revenue</th>
                                            <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Share</th>
                                            <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Cumulative</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 tabular-nums">
                                        @forelse ($trend['topProducts'] as $index => $product)
                                            <tr>
                                                <td class="px-4 py-2 text-gray-500">{{ $index + 1 }}</td>
                                                <td class="px-4 py-2 text-gray-700">{{ $product['name'] }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ number_format($product['units']) }}</td>
                                                <td class="px-4 py-2 text-right text-gray-900">৳{{ number_format($product['revenue'], 2) }}</td>
                                                <td class="px-4 py-2 text-right text-gray-700">{{ $product['share'] }}%</td>
                                                <td class="px-4 py-2 text-right font-medium text-gray-900">{{ $product['cumulative'] }}%</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No sales yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </x-slot>
                    </x-chart-card>

                    <x-chart-card title="Your customers"
                                  subtitle="Distinct customers buying from you per {{$trend['window']['granularity'] === 'daily' ? 'day' : ($trend['window']['granularity'] === 'weekly' ? 'week' : 'month')}}"
                                  footnote="Repeat rate counts customers with 2+ qualifying orders ÷ all your ordering customers in the window.">
                        <x-chart-line :labels="$acquisition['labels']"
                                     :series="[['name' => 'Customers ordering', 'values' => $acquisition['customers'], 'color' => 'var(--ss-chart-2)', 'area' => true]]"
                                     format="number"
                                     aria-label="Customers ordering from your shop"/>

                        <x-slot name="table">
                            <div class="space-y-3 text-sm">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p class="text-xs font-medium text-gray-500">Repeat customers</p>
                                        <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">
                                            {{ $acquisition['repeatRate'] !== null ? $acquisition['repeatRate'].'%' : '—' }}
                                        </p>
                                        <p class="mt-0.5 text-[11px] text-gray-500">{{ number_format($acquisition['repeatCustomers']) }} of {{ number_format($acquisition['orderingCustomers']) }} ordering</p>
                                    </div>
                                    <div class="rounded-lg bg-gray-50 p-3">
                                        <p class="text-xs font-medium text-gray-500">Avg spend per customer</p>
                                        <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">
                                            {{ $acquisition['avgOrderValuePerCustomer'] !== null ? '৳'.number_format($acquisition['avgOrderValuePerCustomer'], 2) : '—' }}
                                        </p>
                                    </div>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-gray-500">Orders per customer</p>
                                    @if ($acquisition['orderingCustomers'] > 0)
                                        <x-chart-segmented :items="[
                                            ['label' => '1 order', 'value' => $distribution['oneOrder'], 'display' => number_format($distribution['oneOrder']).' customers', 'short' => number_format($distribution['oneOrder']), 'color' => 'var(--ss-chart-1)'],
                                            ['label' => '2 orders', 'value' => $distribution['twoOrders'], 'display' => number_format($distribution['twoOrders']).' customers', 'short' => number_format($distribution['twoOrders']), 'color' => 'var(--ss-chart-2)'],
                                            ['label' => '3+ orders', 'value' => $distribution['threePlus'], 'display' => number_format($distribution['threePlus']).' customers', 'short' => number_format($distribution['threePlus']), 'color' => 'var(--ss-chart-3)'],
                                        ]" variant="thin" :show-legend="true" aria-label="Orders per customer distribution"/>
                                    @else
                                        <x-empty-state class="mt-2" message="No ordering customers in this window."/>
                                    @endif
                                </div>
                            </div>
                        </x-slot>
                    </x-chart-card>
                </div>

                {{-- Row 3: categories + inventory intelligence --}}
                <div class="grid gap-4 lg:grid-cols-3">
                    <x-chart-card title="Revenue by category"
                                  subtitle="Your net sales share per category"
                                  footnote="Uncategorised products are grouped, never dropped.">
                        @if ($categories->count() > 0)
                            <x-chart-bars :labels="$categories->pluck('name')->all()"
                                          :values="$categories->pluck('revenue')->all()"
                                          format="money"
                                          aria-label="Your revenue per category"/>
                        @else
                            <x-empty-state message="No category revenue in this window."/>
                        @endif
                    </x-chart-card>

                    <x-chart-card title="Inventory intelligence" class="lg:col-span-2"
                                  subtitle="Stock health now, burn rate over the window"
                                  footnote="Days-of-stock = current stock ÷ units sold per day. It is a moving estimate that assumes recent pace continues; products with no sales have no honest estimate.">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid grid-cols-3 gap-3">
                                <div class="rounded-lg bg-gray-50 p-3">
                                    <p class="text-xs font-medium text-gray-500">Low stock</p>
                                    <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">{{ number_format($stock['lowStock']) }}</p>
                                    <p class="mt-0.5 text-[11px] text-gray-500">of {{ number_format($stock['total']) }}</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 p-3">
                                    <p class="text-xs font-medium text-gray-500">Out of stock</p>
                                    <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">{{ number_format($stock['outOfStock']) }}</p>
                                </div>
                                <div class="rounded-lg bg-gray-50 p-3">
                                    <p class="text-xs font-medium text-gray-500">Healthy</p>
                                    <p class="mt-1 text-xl font-bold text-gray-900 tabular-nums">{{ number_format(max(0, $stock['total'] - $stock['lowStock'])) }}</p>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-semibold text-gray-700">Running out soonest</p>
                                @if ($riskRows->count() > 0)
                                    <ul class="mt-2 space-y-1.5">
                                        @foreach ($riskRows as $row)
                                            <li class="flex items-center justify-between gap-3 text-xs">
                                                <span class="min-w-0 truncate text-gray-700">{{ $row['name'] }}</span>
                                                <span class="shrink-0 tabular-nums text-gray-600">
                                                    {{ number_format($row['stock']) }} left · {{ number_format($row['burnPerDay'], 1) }}/day ·
                                                    <span class="font-medium {{ ($row['daysOfStock'] !== null && $row['daysOfStock'] <= 7) ? 'text-[color:var(--ss-status-critical)]' : 'text-gray-800' }}">
                                                        {{ $row['daysOfStock'] !== null ? number_format($row['daysOfStock'], 1).' days' : '—' }}
                                                    </span>
                                                </span>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <x-empty-state class="mt-2" message="No recorded sales pace in this window." hint="Days-of-stock appears once items sell."/>
                                @endif
                            </div>
                        </div>
                    </x-chart-card>
                </div>

                <p class="text-xs text-gray-400">
                    Need vouchers, payments, movers or the order-time heatmap? The full filterable
                    report lives in
                    <a href="{{ route('owner.reports') }}" class="font-semibold text-indigo-600 hover:text-indigo-800">Reports</a>.
                </p>
            </section>

            {{-- Recent orders --}}
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                     data-reveal>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-5 sm:p-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Recent Orders</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Latest orders containing your products.
                        </p>
                    </div>

                    <a href="{{ route('owner.order.manage') }}"
                       class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        Manage Orders →
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[650px] text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-4 font-semibold">Order</th>
                                <th class="px-5 py-4 font-semibold">Customer</th>
                                <th class="px-5 py-4 font-semibold">Date</th>
                                <th class="px-5 py-4 font-semibold">Status</th>
                                <th class="px-5 py-4 text-right font-semibold">Order Total</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentOrders as $order)

                                <tr class="transition hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-5 py-4 font-semibold text-gray-900">
                                        {{ $order->order_number ?: '#'.$order->id }}
                                    </td>

                                    <td class="px-5 py-4 text-gray-700">
                                        {{ $order->customer_name ?: ($order->user?->name ?? 'Customer') }}
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-gray-500">
                                        {{ $order->created_at?->format('d M Y') }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach ($order->items->groupBy('status') as $itemStatus => $statusItems)
                                                @php
                                                    $statusClasses = match (strtolower((string) $itemStatus)) {
                                                        'pending' => 'bg-amber-100 text-amber-800',
                                                        'processing' => 'bg-blue-100 text-blue-800',
                                                        'shipped' => 'bg-violet-100 text-violet-800',
                                                        'delivered' => 'bg-green-100 text-green-800',
                                                        'cancelled' => 'bg-red-100 text-red-800',
                                                        default => 'bg-gray-100 text-gray-700',
                                                    };
                                                @endphp
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                                    {{ ucfirst($itemStatus) }} ({{ $statusItems->count() }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>

                                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-gray-900">
                                        ৳{{ number_format((float) $order->items->sum('subtotal'), 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center">
                                        <p class="font-semibold text-gray-800">No orders yet</p>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Orders containing your products will appear here.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Inventory alerts --}}
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                     data-reveal>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-5 sm:p-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Low Stock Alerts</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Products with five or fewer units remaining.
                        </p>
                    </div>

                    <a href="{{ route('owner.products.index') }}"
                       class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        Manage Inventory →
                    </a>
                </div>

                <div class="divide-y divide-gray-100">
                    @forelse ($lowStockProducts as $product)
                        <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-gray-900">
                                    {{ $product->name }}
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    Price: ৳{{ number_format((float) $product->price, 2) }}
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $product->stock <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }}">
                                    {{ $product->stock <= 0 ? 'Out of stock' : $product->stock.' left' }}
                                </span>

                                <a href="{{ route('owner.products.edit', $product) }}"
                                   class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                                    Edit
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <p class="font-semibold text-gray-800">Inventory looks good!</p>
                            <p class="mt-1 text-sm text-gray-500">
                                No products currently have five or fewer units.
                            </p>
                        </div>
                    @endforelse
                </div>
            </section>

            {{-- Quick actions --}}
            <section data-reveal>
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Quick Actions</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Jump straight to the tools you use most.
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['Manage Products', 'Add, edit and maintain your catalogue.', route('owner.products.index'), '📦'],
                        ['Manage Orders', 'Review orders and update their status.', route('owner.order.manage'), '🛍️'],
                        ['Inventory History', 'Review recorded stock movements.', route('owner.inventory.history'), '📋'],
                        ['Sales & Analytics', 'Explore sales and product performance.', route('owner.reports'), '📈'],
                    ] as [$title, $description, $url, $icon])
                        <a href="{{ $url }}"
                           class="ss-motion-card group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300"
                           data-reveal>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-xl transition group-hover:bg-indigo-100">
                                {{ $icon }}
                            </span>

                            <h4 class="mt-4 font-bold text-gray-900">{{ $title }}</h4>

                            <p class="mt-2 text-sm leading-6 text-gray-500">
                                {{ $description }}
                            </p>

                            <span class="mt-4 inline-block text-sm font-semibold text-indigo-600">
                                Open section →
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>

            {{-- Storefront shortcut --}}
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-indigo-100 bg-indigo-50 p-5"
                 data-reveal>
                <div>
                    <h3 class="font-bold text-gray-900">View the customer storefront</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Preview the products customers can currently browse.
                    </p>
                </div>

                <a href="{{ route('shop.index') }}"
                   class="rounded-xl border border-indigo-200 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                    Open Storefront →
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
