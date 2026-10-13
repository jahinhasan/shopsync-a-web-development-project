<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Sales Reports & Analytics
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <form method="GET" action="{{ route('admin.reports') }}"
                  class="flex flex-wrap items-end gap-4 rounded-lg bg-white p-5 shadow">
                <div>
                    <label for="from" class="mb-1 block text-sm font-medium">From</label>
                    <input id="from" name="from" type="date" value="{{ $from }}"
                           required class="rounded-md border-gray-300">
                </div>
                <div>
                    <label for="to" class="mb-1 block text-sm font-medium">To</label>
                    <input id="to" name="to" type="date" value="{{ $to }}"
                           required class="rounded-md border-gray-300">
                </div>
                <button class="rounded-md bg-indigo-600 px-5 py-2 text-white hover:bg-indigo-700">
                    Apply Filter
                </button>
                <a href="{{ route('admin.reports') }}"
                   class="rounded-md border px-5 py-2 text-gray-700">
                    Reset
                </a>
            </form>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">Sales in Selected Period</p>
                    <p class="mt-2 text-2xl font-bold">
                        ৳{{ number_format((float) $totalSales, 2) }}
                    </p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">Orders in Selected Period</p>
                    <p class="mt-2 text-2xl font-bold">{{ $totalOrders }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">Total Users</p>
                    <p class="mt-2 text-2xl font-bold">{{ $totalUsers }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">Total Products</p>
                    <p class="mt-2 text-2xl font-bold">{{ $totalProducts }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">Total Categories</p>
                    <p class="mt-2 text-2xl font-bold">{{ $totalCategories }}</p>
                </div>
            </div>

            <section class="rounded-lg bg-white p-5 shadow">
                <h3 class="mb-4 text-lg font-semibold">Daily Sales</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-right">Orders</th>
                                <th class="px-4 py-3 text-right">Sales</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($dailySales as $day)
                                <tr>
                                    <td class="px-4 py-3">{{ $day->sale_date }}</td>
                                    <td class="px-4 py-3 text-right">{{ $day->orders_count }}</td>
                                    <td class="px-4 py-3 text-right">
                                        ৳{{ number_format((float) $day->sales, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-5 text-center text-gray-500">
                                        No sales in this period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg bg-white p-5 shadow">
                <h3 class="mb-4 text-lg font-semibold">Monthly Sales (Selected Period)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Month</th>
                                <th class="px-4 py-3 text-right">Orders</th>
                                <th class="px-4 py-3 text-right">Sales</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($monthlySales as $month)
                                <tr>
                                    <td class="px-4 py-3">{{ $month->sale_month }}</td>
                                    <td class="px-4 py-3 text-right">{{ $month->orders_count }}</td>
                                    <td class="px-4 py-3 text-right">
                                        ৳{{ number_format((float) $month->sales, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-5 text-center text-gray-500">
                                        No monthly sales found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg bg-white p-5 shadow">
                <h3 class="mb-4 text-lg font-semibold">Best-Selling Products</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Product</th>
                                <th class="px-4 py-3 text-right">Quantity Sold</th>
                                <th class="px-4 py-3 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($bestSellingProducts as $product)
                                <tr>
                                    <td class="px-4 py-3">{{ $product->product_name }}</td>
                                    <td class="px-4 py-3 text-right">{{ $product->quantity_sold }}</td>
                                    <td class="px-4 py-3 text-right">
                                        ৳{{ number_format((float) $product->revenue, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-5 text-center text-gray-500">
                                        No product sales found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg bg-white p-5 shadow">
                <h3 class="mb-4 text-lg font-semibold">Low Stock (5 or Fewer)</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Product</th>
                                <th class="px-4 py-3 text-left">Owner</th>
                                <th class="px-4 py-3 text-right">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($lowStockProducts as $product)
                                <tr>
                                    <td class="px-4 py-3">{{ $product->name }}</td>
                                    <td class="px-4 py-3">{{ $product->owner?->name ?? 'Unknown' }}</td>
                                    <td class="px-4 py-3 text-right">{{ $product->stock }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-5 text-center text-gray-500">
                                        No low-stock products.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </div>
</x-app-layout>