
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Admin Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Welcome, {{ auth()->user()->name }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Monitor ShopSync users, products, categories and orders.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['Total Users', $totalUsers],
                    ['Total Products', $totalProducts],
                    ['Total Categories', $totalCategories],
                    ['Total Orders', $totalOrders],
                    ['Processed Sales', '৳' . number_format((float) $totalSales, 2)],
                ] as [$title, $value])
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-gray-500">{{ $title }}</p>
                        <h3 class="mt-2 text-2xl font-bold text-gray-900">
                            {{ $value }}
                        </h3>
                    </div>
                @endforeach
            </div>

            <section>
                <h3 class="mb-4 text-lg font-semibold text-gray-900">
                    Administration
                </h3>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a href="{{ route('admin.categories.index') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">Category Management</h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Create, edit and manage product categories.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            Manage Categories →
                        </span>
                    </a>

                    <a href="{{ route('admin.reports') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">Sales & Analytics</h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Review sales, monthly performance and best-selling products.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            View Reports →
                        </span>
                    </a>

                    <a href="{{ route('shop.index') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">View Storefront</h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Inspect the customer-facing product catalogue.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            Open Store →
                        </span>
                    </a>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 p-5">
                    <h3 class="text-lg font-semibold text-gray-900">
                        Recent Orders
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-gray-600">
                            <tr>
                                <th class="p-4">Order Number</th>
                                <th class="p-4">Customer</th>
                                <th class="p-4">Total</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Date</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse ($recentOrders as $order)
                                <tr>
                                    <td class="p-4 font-medium text-gray-900">
                                        {{ $order->order_number }}
                                    </td>
                                    <td class="p-4">{{ $order->customer_name }}</td>
                                    <td class="whitespace-nowrap p-4">
                                        ৳{{ number_format((float) $order->total, 2) }}
                                    </td>
                                    <td class="p-4">
                                        <span class="inline-block rounded-full bg-gray-100 px-3 py-1 capitalize text-gray-700">
                                            {{ $order->status }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap p-4">
                                        {{ $order->created_at->format('d M Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-6 text-center text-gray-500">
                                        No orders have been placed yet.
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
