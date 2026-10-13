<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            My Business Analytics
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <form method="GET" action="{{ route('owner.reports') }}"
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
                <button class="rounded-md bg-indigo-600 px-5 py-2 text-white">
                    Apply Filter
                </button>
                <a href="{{ route('owner.reports') }}"
                   class="rounded-md border px-5 py-2 text-gray-700">
                    Reset
                </a>
            </form>

            @if ($errors->any())
                <div class="rounded bg-red-100 p-4 text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">My Sales</p>
                    <p class="mt-2 text-2xl font-bold">
                        ৳{{ number_format((float) $totalSales, 2) }}
                    </p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">Orders Containing My Products</p>
                    <p class="mt-2 text-2xl font-bold">{{ $totalOrders }}</p>
                </div>
                <div class="rounded-lg bg-white p-5 shadow">
                    <p class="text-sm text-gray-500">Items Sold</p>
                    <p class="mt-2 text-2xl font-bold">{{ $totalItemsSold }}</p>
                </div>
            </div>

            <section class="rounded-lg bg-white p-5 shadow">
                <h3 class="mb-4 text-lg font-semibold">Daily Sales</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-right">Quantity Sold</th>
                                <th class="px-4 py-3 text-right">Sales</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($dailySales as $day)
                                <tr>
                                    <td class="px-4 py-3">{{ $day->sale_date }}</td>
                                    <td class="px-4 py-3 text-right">{{ $day->quantity_sold }}</td>
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
                <h3 class="mb-4 text-lg font-semibold">My Best-Selling Products</h3>
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
                <h3 class="mb-4 text-lg font-semibold">My Low-Stock Products</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Product</th>
                                <th class="px-4 py-3 text-right">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($lowStockProducts as $product)
                                <tr>
                                    <td class="px-4 py-3">{{ $product->name }}</td>
                                    <td class="px-4 py-3 text-right">{{ $product->stock }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-5 text-center text-gray-500">
                                        No low-stock products.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-lg bg-white p-5 shadow">
                <h3 class="mb-4 text-lg font-semibold">Recent Inventory Activity</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left">Date</th>
                                <th class="px-4 py-3 text-left">Product</th>
                                <th class="px-4 py-3 text-left">Type</th>
                                <th class="px-4 py-3 text-right">Change</th>
                                <th class="px-4 py-3 text-right">Stock After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($inventoryTransactions as $transaction)
                                <tr>
                                    <td class="px-4 py-3">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3">{{ $transaction->product?->name ?? 'Deleted product' }}</td>
                                    <td class="px-4 py-3">{{ ucfirst($transaction->type) }}</td>
                                    <td class="px-4 py-3 text-right">{{ $transaction->quantity_change }}</td>
                                    <td class="px-4 py-3 text-right">{{ $transaction->quantity_after }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-5 text-center text-gray-500">
                                        No inventory activity found.
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