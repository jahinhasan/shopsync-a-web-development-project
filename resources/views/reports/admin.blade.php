<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Sales Reports & Analytics
        </h2>
    </x-slot>

    @php
        

        $series = $payload['series'];
        $totals = $payload['totals'];
        $deltas = $payload['deltas'];
        $previous = $payload['previous'];
        $hasCompare = $deltas !== null;
        $deltaLabel = 'vs previous period';

        $trendSeries = [['name' => 'Net sales', 'values' => $series['netSales'], 'color' => 'var(--ss-chart-1)', 'area' => true]];

        if ($previous !== null && ($previous['hasData'] ?? false)) {
            $trendSeries[] = ['name' => 'Previous period', 'values' => $previous['netSales'], 'color' => 'var(--ss-chart-dim)', 'dashed' => true];
        }

        // Status colors are reserved semantics, not series identity.
        $statusColors = [
            'delivered' => 'var(--ss-status-good)',
            'pending' => 'var(--ss-status-warning)',
            'cancelled' => 'var(--ss-status-critical)',
            'processing' => '#6b7280',
            'shipped' => '#475569',
        ];

        $statusSegments = collect($payload['statusDistribution'])
            ->map(fn (array $row): array => [
                'label' => ucfirst($row['status']),
                'value' => $row['count'],
                'display' => \App\Services\Analytics\Formats::number($row['count']).' items',
                'short' => \App\Services\Analytics\Formats::number($row['count']),
                'color' => $statusColors[$row['status']] ?? '#9ca3af',
            ])->all();

        // Payment methods are nominal categories with a fixed color per
        // entity, so filtering never repaints a survivor.
        $methodColors = [
            'cod' => 'var(--ss-chart-1)',
            'bkash' => 'var(--ss-chart-2)',
            'nagad' => 'var(--ss-chart-3)',
            'rocket' => 'var(--ss-chart-4)',
        ];

        $paymentSegments = collect($payload['payments']['mix'])
            ->map(fn (array $row): array => [
                'label' => ucfirst($row['method']),
                'value' => $row['count'],
                'display' => \App\Services\Analytics\Formats::number($row['count']).' orders',
                'short' => \App\Services\Analytics\Formats::percent($row['share']),
                'color' => $methodColors[strtolower($row['method'])] ?? '#9ca3af',
            ])->all();

        $histogram = $payload['orderHistogram'];
        $acquisition = $payload['acquisition'];
        $heatmap = $payload['heatmap'];
        $movers = $payload['productMovers'];
    @endphp

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <x-filter-bar route-name="admin.reports" :from="$window['from']->toDateString()"
                         :to="$window['to']->toDateString()" :granularity="$window['granularity']"
                         :compare="$window['compare']" :preset="$window['preset']"/>

            @if ($errors->any())
                <div class="rounded-lg bg-red-100 p-4 text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Key figures for the selected window --}}
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <x-stat-card label="Net sales (selected period)" class="lg:col-span-2" hero
                            :value="\App\Services\Analytics\Formats::money($totals['netSales'])"
                            :delta="$hasCompare ? $deltas['netSales'] : null" :delta-label="$deltaLabel"
                            :sparkline="$series['netSales']"/>
                <x-stat-card label="Orders with sales" :value="\App\Services\Analytics\Formats::number($totals['orders'])"
                            :delta="$hasCompare ? $deltas['orders'] : null" :delta-label="$deltaLabel"/>
                <x-stat-card label="Average order value" :value="\App\Services\Analytics\Formats::money($totals['aov'])"
                            :delta="$hasCompare ? $deltas['aov'] : null" :delta-label="$deltaLabel"/>
                <x-stat-card label="Distinct customers" :value="\App\Services\Analytics\Formats::number($totals['customers'])"/>
            </section>

            {{-- Net sales trend + its table twin --}}
            <x-chart-card title="Net sales trend"
                          subtitle="{{ ucfirst($window['granularity']) }} buckets, {{ $window['from']->format('j M Y') }} → {{ $window['to']->format('j M Y') }}"
                          footnote="Net sales count only items in processing, shipped or delivered status. The dashed gray line is the fitted trend; the dotted continuation is its projection — an estimate, never a fact. Ringed points mark statistically unusual buckets (z-score above 2), listed in the table below the chart.">
                <x-chart-line :labels="$series['labels']" :series="$trendSeries"
                             :regression="$series['regression']" :projection="$series['projection']"
                             :anomalies="$series['anomalies']" format="money"
                             aria-label="Net sales trend for the selected period"/>

                <x-slot name="table">
                    <div class="max-h-80 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Bucket</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Orders</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Units</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Net sales</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Cumulative</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 tabular-nums">
                                @forelse ($series['labels'] as $index => $label)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-700">{{ $label }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::number($series['orders'][$index]) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::number($series['units'][$index]) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-900">{{ \App\Services\Analytics\Formats::money($series['netSales'][$index]) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-500">{{ \App\Services\Analytics\Formats::money($series['cumulative'][$index]) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No buckets in this window.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </x-slot>
            </x-chart-card>

            {{-- Statistical anomalies, when the period is long enough --}}
            @if (($series['anomalies'] ?? null) !== null && collect($series['anomalies'])->contains(fn ($a): bool => $a['flagged']))
                <x-chart-card title="Unusual buckets"
                              subtitle="Days whose net sales sit more than two standard deviations from the period average"
                              footnote="A z-score above 2 marks an outlier worth investigating — a promotion, a bulk order or a data issue. Unusual means atypical, not good or bad.">
                    <ul class="divide-y divide-gray-100">
                        @foreach ($series['anomalies'] as $index => $anomaly)
                            @if ($anomaly['flagged'])
                                <li class="flex items-center justify-between gap-4 py-2.5 text-sm">
                                    <span class="font-medium text-gray-800">{{ $series['labels'][$index] }}</span>
                                    <span class="text-gray-600">
                                        {{ \App\Services\Analytics\Formats::money($series['netSales'][$index]) }}
                                        <span class="ml-2 rounded-md bg-rose-50 px-1.5 py-0.5 text-xs font-medium tabular-nums text-[color:var(--ss-delta-down)]">
                                            z {{ number_format((float) $anomaly['z'], 1) }}
                                        </span>
                                    </span>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </x-chart-card>
            @endif

            {{-- Order value distribution + customer growth --}}
            <section class="grid gap-4 lg:grid-cols-2">
                @if ($histogram !== null)
                    <x-chart-card title="Order value distribution"
                                  subtitle="Fulfilled orders in the window"
                                  footnote="The median is the middle order — half of orders fall below it. A median far below the mean means a few large orders pull the average up; read the median for the typical order.">
                        <x-chart-bars :labels="collect($histogram['bins'])->map(fn ($bin) => \App\Services\Analytics\Formats::moneyCompact($bin['from']).'–'.\App\Services\Analytics\Formats::moneyCompact($bin['to']))->all()"
                                     :values="collect($histogram['bins'])->pluck('count')->all()"
                                     color="var(--ss-chart-2)" format="number" :height="220"
                                     aria-label="Distribution of order values"/>
                        <div class="mt-3 grid grid-cols-3 gap-3 border-t border-gray-100 pt-3 text-center">
                            <div>
                                <p class="text-xs text-gray-500">Mean</p>
                                <p class="text-sm font-semibold tabular-nums text-gray-900">{{ \App\Services\Analytics\Formats::money($histogram['mean']) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Median (P50)</p>
                                <p class="text-sm font-semibold tabular-nums text-gray-900">{{ \App\Services\Analytics\Formats::money($histogram['median']) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Top decile (P90)</p>
                                <p class="text-sm font-semibold tabular-nums text-gray-900">{{ \App\Services\Analytics\Formats::money($histogram['p90']) }}</p>
                            </div>
                        </div>
                    </x-chart-card>
                @else
                    <x-chart-card title="Order value distribution">
                        <p class="py-16 text-center text-sm text-gray-500">
                            No fulfilled orders in this period.
                        </p>
                    </x-chart-card>
                @endif

                <x-chart-card title="{{ $acquisition['metric'] === 'signups' ? 'New customers' : 'Customers ordering' }}"
                              subtitle="{{ $acquisition['metric'] === 'signups' ? 'New customer accounts per bucket' : 'Distinct customers ordering from any shop, per bucket' }}"
                              footnote="Repeat rate counts customers of this window who placed two or more qualifying orders.">
                    <x-chart-bars :labels="$acquisition['labels']" :values="$acquisition['customers']"
                                 color="var(--ss-chart-1)" format="number" :height="220"
                                 aria-label="Customers per bucket"/>
                    <div class="mt-3 grid grid-cols-3 gap-3 border-t border-gray-100 pt-3 text-center">
                        <div>
                            <p class="text-xs text-gray-500">Ordering customers</p>
                            <p class="text-sm font-semibold tabular-nums text-gray-900">{{ \App\Services\Analytics\Formats::number($acquisition['orderingCustomers']) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Repeat customers</p>
                            <p class="text-sm font-semibold tabular-nums text-gray-900">{{ \App\Services\Analytics\Formats::number($acquisition['repeatCustomers']) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Repeat rate</p>
                            <p class="text-sm font-semibold tabular-nums text-gray-900">{{ $acquisition['repeatRate'] === null ? '—' : \App\Services\Analytics\Formats::percent($acquisition['repeatRate']) }}</p>
                        </div>
                    </div>
                </x-chart-card>
            </section>

            {{-- Top products: Pareto concentration --}}
            <x-chart-card title="Top products — revenue concentration"
                          subtitle="Ranked by net sales with cumulative share"
                          footnote="Pareto view: the cumulative column shows how much of period revenue the top products account for. Share is a fact; the concentration itself suggests where attention pays off.">
                @if (count($payload['topProducts']) > 0)
                    <x-chart-rank-bars :items="collect($payload['topProducts'])->map(fn ($p) => [
                        'name' => $p['name'],
                        'value' => $p['revenue'],
                        'display' => \App\Services\Analytics\Formats::money($p['revenue']).' ('.\App\Services\Analytics\Formats::percent($p['share']).')',
                    ])->all()" aria-label="Top products by revenue share"/>

                    <x-slot name="table">
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
                                @foreach ($payload['topProducts'] as $index => $product)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-500">{{ $index + 1 }}</td>
                                        <td class="px-4 py-2 text-gray-800">{{ $product['name'] }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::number($product['units']) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-900">{{ \App\Services\Analytics\Formats::money($product['revenue']) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::percent($product['share']) }}</td>
                                        <td class="px-4 py-2 text-right font-medium text-gray-900">{{ \App\Services\Analytics\Formats::percent($product['cumulative']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </x-slot>
                @else
                    <p class="py-16 text-center text-sm text-gray-500">No product sales in this period.</p>
                @endif
            </x-chart-card>

            {{-- Category performance + order status mix --}}
            <section class="grid gap-4 lg:grid-cols-2">
                <x-chart-card title="Category performance"
                              subtitle="Net sales grouped by product category"
                              footnote="Uncategorised products are grouped together so no revenue disappears from the total.">
                    @if (count($payload['categoryPerformance']) > 0)
                        <x-chart-rank-bars :items="collect($payload['categoryPerformance'])->take(7)->map(fn ($c) => [
                            'name' => $c['name'],
                            'value' => $c['revenue'],
                            'display' => \App\Services\Analytics\Formats::money($c['revenue']).' ('.\App\Services\Analytics\Formats::percent($c['share']).')',
                        ])->all()" aria-label="Revenue by category"/>

                        <x-slot name="table">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Category</th>
                                        <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Units</th>
                                        <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Revenue</th>
                                        <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Share</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 tabular-nums">
                                    @foreach ($payload['categoryPerformance'] as $category)
                                        <tr>
                                            <td class="px-4 py-2 text-gray-800">{{ $category['name'] }}</td>
                                            <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::number($category['units']) }}</td>
                                            <td class="px-4 py-2 text-right text-gray-900">{{ \App\Services\Analytics\Formats::money($category['revenue']) }}</td>
                                            <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::percent($category['share']) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </x-slot>
                    @else
                        <p class="py-16 text-center text-sm text-gray-500">No category sales in this period.</p>
                    @endif
                </x-chart-card>

                <x-chart-card title="Order status mix"
                              subtitle="All order items placed in the window, by current status"
                              footnote="Cancelled items are shown, not hidden — they explain gaps between orders placed and net sales.">
                    @if (count($statusSegments) > 0)
                        <x-chart-segmented :items="$statusSegments" variant="tall"
                                          aria-label="Order item status distribution"/>
                        <table class="mt-4 min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Status</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Items</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Share</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 tabular-nums">
                                @foreach ($payload['statusDistribution'] as $row)
                                    <tr>
                                        <td class="px-4 py-2"><x-ss-badge :value="$row['status']"/></td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::number($row['count']) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::percent($row['share']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="py-16 text-center text-sm text-gray-500">No order items in this period.</p>
                    @endif
                </x-chart-card>
            </section>

            {{-- Voucher effectiveness (descriptive) --}}
            @if (count($payload['vouchers']['usage']) > 0 || $payload['vouchers']['issued'] > 0)
                <x-chart-card title="Voucher effectiveness"
                              subtitle="Issuance, redemption and revenue context"
                              footnote="Voucher-order revenue is shown beside non-voucher revenue for context. The two numbers describe different order sets; they do not prove vouchers caused the difference.">
                    <div class="grid gap-4 sm:grid-cols-4">
                        <div class="rounded-xl bg-gray-50 p-4 text-center">
                            <p class="text-xs text-gray-500">Vouchers issued</p>
                            <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-900">{{ \App\Services\Analytics\Formats::number($payload['vouchers']['issued']) }}</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4 text-center">
                            <p class="text-xs text-gray-500">Redeemed</p>
                            <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-900">{{ \App\Services\Analytics\Formats::number($payload['vouchers']['redeemed']) }}</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4 text-center">
                            <p class="text-xs text-gray-500">Redemption rate</p>
                            <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-900">{{ $payload['vouchers']['redemptionRate'] === null ? '—' : \App\Services\Analytics\Formats::percent($payload['vouchers']['redemptionRate']) }}</p>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4 text-center">
                            <p class="text-xs text-gray-500">Discount on orders</p>
                            <p class="mt-1 text-2xl font-semibold tabular-nums text-gray-900">{{ \App\Services\Analytics\Formats::money($totals['discountOnOrders']) }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 lg:grid-cols-2">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Code (this window)</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Orders</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Discount given</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 tabular-nums">
                                @forelse ($payload['vouchers']['usage'] as $voucher)
                                    <tr>
                                        <td class="px-4 py-2 font-medium text-gray-800">{{ $voucher['code'] }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::number($voucher['orders']) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-900">{{ \App\Services\Analytics\Formats::money($voucher['discount']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">No vouchers redeemed in this window.</td></tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="space-y-3">
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Revenue on voucher orders</span>
                                    <span class="font-semibold tabular-nums text-gray-900">{{ \App\Services\Analytics\Formats::money($payload['vouchers']['salesWithVoucher']) }}</span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100">
                                    @php
                                        $voucherTotal = $payload['vouchers']['salesWithVoucher'] + $payload['vouchers']['salesWithoutVoucher'];
                                    @endphp
                                    <div class="h-2 rounded-full bg-[color:var(--ss-chart-1)]"
                                         style="width: {{ $voucherTotal > 0 ? round(($payload['vouchers']['salesWithVoucher'] / $voucherTotal) * 100, 1) : 0 }}%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">Revenue on non-voucher orders</span>
                                    <span class="font-semibold tabular-nums text-gray-900">{{ \App\Services\Analytics\Formats::money($payload['vouchers']['salesWithoutVoucher']) }}</span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100">
                                    <div class="h-2 rounded-full bg-[color:var(--ss-chart-2)]"
                                         style="width: {{ $voucherTotal > 0 ? round(($payload['vouchers']['salesWithoutVoucher'] / $voucherTotal) * 100, 1) : 0 }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-chart-card>
            @endif

            {{-- Payment health + shop concentration (admin-only views) --}}
            <section class="grid gap-4 lg:grid-cols-2">
                <x-chart-card title="Payment methods"
                              subtitle="All orders placed in the window"
                              footnote="Latency covers mobile-money orders (bkash/nagad): time from checkout to the customer submitting the payment reference. Owner verification time is not recorded by the system, so it is not shown.">
                    @if (count($paymentSegments) > 0)
                        <x-chart-segmented :items="$paymentSegments" variant="thin" aria-label="Payment method mix"/>
                        <div class="mt-3 flex items-center justify-between border-t border-gray-100 pt-3 text-sm">
                            <span class="text-gray-600">Median payment submission time</span>
                            <span class="font-semibold tabular-nums text-gray-900">
                                {{ \App\Services\Analytics\Formats::duration($payload['payments']['submissionMedianSeconds']) }}
                                <span class="ml-1 text-xs font-normal text-gray-500">({{ $payload['payments']['submissionSamples'] }} mobile-money orders)</span>
                            </span>
                        </div>
                        <table class="mt-3 min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Method</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Orders</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Share</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 tabular-nums">
                                @foreach ($payload['payments']['mix'] as $row)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-800">{{ ucfirst($row['method']) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::number($row['count']) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::percent($row['share']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="py-16 text-center text-sm text-gray-500">No orders in this period.</p>
                    @endif
                </x-chart-card>

                <x-chart-card title="Shop concentration"
                              subtitle="Net sales per approved shop"
                              footnote="Share shows how evenly the marketplace sells across its shops; a heavily top-weighted share signals dependency on one seller.">
                    @if (count($payload['shopPerformance'] ?? []) > 0)
                        <x-chart-rank-bars :items="collect($payload['shopPerformance'])->take(8)->map(fn ($shop) => [
                            'name' => $shop['name'],
                            'value' => $shop['revenue'],
                            'display' => \App\Services\Analytics\Formats::money($shop['revenue']).' ('.\App\Services\Analytics\Formats::percent($shop['share']).')',
                        ])->all()" aria-label="Net sales per shop"/>
                    @else
                        <p class="py-16 text-center text-sm text-gray-500">No shop sales in this period.</p>
                    @endif
                </x-chart-card>
            </section>

            @if ($heatmap !== null)
                <x-chart-card title="When customers order"
                              subtitle="Fulfilled orders by weekday and two-hour slot"
                              footnote="Darker cells are busier. Rhythms need at least two weeks of data — with less, this chart stays hidden rather than guessing.">
                    <x-chart-heatmap :cells="$heatmap['cells']" :max="$heatmap['max']"/>
                </x-chart-card>
            @endif

            {{-- Product movers --}}
            @if (count($movers['risers']) > 0 || count($movers['decliners']) > 0 || $movers['newProducts'] > 0)
                <x-chart-card title="Product movers"
                              subtitle="Revenue change versus the previous period of the same length"
                              footnote="Percentage change needs a non-zero previous period; products new to this period are counted separately.">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <h4 class="text-sm font-semibold text-emerald-700">Growing</h4>
                            <ul class="mt-2 divide-y divide-gray-100">
                                @forelse ($movers['risers'] as $mover)
                                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                        <span class="truncate text-gray-800">{{ $mover['name'] }}</span>
                                        <span class="shrink-0 font-medium tabular-nums text-[color:var(--ss-delta-up)]">{{ \App\Services\Analytics\Formats::signedPercent($mover['change']) }}</span>
                                    </li>
                                @empty
                                    <li class="py-2 text-sm text-gray-500">None in this window.</li>
                                @endforelse
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-rose-700">Declining</h4>
                            <ul class="mt-2 divide-y divide-gray-100">
                                @forelse ($movers['decliners'] as $mover)
                                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                        <span class="truncate text-gray-800">{{ $mover['name'] }}</span>
                                        <span class="shrink-0 font-medium tabular-nums text-[color:var(--ss-delta-down)]">{{ \App\Services\Analytics\Formats::signedPercent($mover['change']) }}</span>
                                    </li>
                                @empty
                                    <li class="py-2 text-sm text-gray-500">None in this window.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
                    @if ($movers['newProducts'] > 0)
                        <p class="mt-3 text-sm text-gray-600">
                            {{ \App\Services\Analytics\Formats::number($movers['newProducts']) }} product(s) sold in this window but not in the previous one.
                        </p>
                    @endif
                </x-chart-card>
            @endif

            {{-- Inventory burn-down --}}
            <x-chart-card title="Inventory risk"
                          subtitle="Consumption rate from the stock ledger, projected days remaining"
                          footnote="Burn rate divides units sold in the window by the window length; days of stock divides current stock by that rate. It assumes the recent pace continues — treat it as a moving estimate, especially over short windows.">
                @if (count($payload['inventoryRisk']) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Product</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Stock</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Sold in window</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Burn / day</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Days of stock</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Restocks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 tabular-nums">
                                @foreach ($payload['inventoryRisk'] as $risk)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-800">{{ $risk['name'] }}</td>
                                        <td class="px-4 py-2 text-right {{ $risk['stock'] <= 0 ? 'font-semibold text-rose-700' : 'text-gray-700' }}">{{ \App\Services\Analytics\Formats::number($risk['stock']) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::number($risk['soldUnits']) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::number($risk['burnPerDay']) }}</td>
                                        <td class="px-4 py-2 text-right font-medium {{ ($risk['daysOfStock'] !== null && $risk['daysOfStock'] <= 3) ? 'text-rose-700' : 'text-gray-900' }}">
                                            {{ $risk['daysOfStock'] === null ? '—' : \App\Services\Analytics\Formats::number($risk['daysOfStock']) }}
                                        </td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::number($risk['restocks']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="py-16 text-center text-sm text-gray-500">
                        No stock movement in this window — nothing to project yet.
                    </p>
                @endif
            </x-chart-card>

            {{-- Long-horizon tables (unchanged from the classic report) --}}
            <section class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-base font-semibold text-gray-900">Monthly Sales (all time)</h3>
                    <div class="max-h-80 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Month</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Orders</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Sales</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 tabular-nums">
                                @forelse ($monthlySales as $month)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-700">{{ $month->sale_month }}</td>
                                        <td class="px-4 py-2 text-right text-gray-700">{{ \App\Services\Analytics\Formats::number((float) $month->orders_count) }}</td>
                                        <td class="px-4 py-2 text-right text-gray-900">{{ \App\Services\Analytics\Formats::money((float) $month->sales) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">No sales recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                    <h3 class="mb-4 text-base font-semibold text-gray-900">Low-Stock Products</h3>
                    <div class="max-h-80 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Product</th>
                                    <th class="px-4 py-2.5 text-left font-semibold text-gray-600">Owner</th>
                                    <th class="px-4 py-2.5 text-right font-semibold text-gray-600">Stock</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse ($lowStockProducts as $product)
                                    <tr>
                                        <td class="px-4 py-2 text-gray-800">{{ $product->name }}</td>
                                        <td class="px-4 py-2 text-gray-600">{{ $product->owner?->name ?? 'Unknown' }}</td>
                                        <td class="px-4 py-2 text-right">
                                            <x-ss-badge :value="$product->stock <= 0 ? 'cancelled' : 'pending'"/>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">No low-stock products.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
