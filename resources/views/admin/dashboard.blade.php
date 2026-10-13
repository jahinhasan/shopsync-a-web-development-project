
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Admin Dashboard</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">

            <div class="rounded-lg border bg-white p-5">
                <p class="text-sm text-gray-500">Total Users</p>
                <p class="mt-2 text-2xl font-bold">{{ $totalUsers }}</p>
            </div>

            <div class="rounded-lg border bg-white p-5">
                <p class="text-sm text-gray-500">Total Products</p>
                <p class="mt-2 text-2xl font-bold">{{ $totalProducts }}</p>
            </div>

            <div class="rounded-lg border bg-white p-5">
                <p class="text-sm text-gray-500">Categories</p>
                <p class="mt-2 text-2xl font-bold">{{ $totalCategories }}</p>
            </div>

            <div class="rounded-lg border bg-white p-5">
                <p class="text-sm text-gray-500">Total Orders</p>
                <p class="mt-2 text-2xl font-bold">{{ $totalOrders }}</p>
            </div>

        </div>

        <div class="rounded-lg border bg-white p-5">
            <p class="text-sm text-gray-500">
                Sales from Processing, Shipped and Delivered Orders
            </p>

            <p class="mt-2 text-3xl font-bold">
                ৳{{ number_format($totalSales, 2) }}
            </p>
        </div>

        <div class="overflow-x-auto rounded-lg border bg-white p-5">
            <h3 class="mb-4 text-lg font-semibold">Recent Orders</h3>

            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="px-3 py-3">Order Number</th>
                        <th class="px-3 py-3">Customer</th>
                        <th class="px-3 py-3">Total</th>
                        <th class="px-3 py-3">Status</th>
                        <th class="px-3 py-3">Date</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr class="border-b">
                            <td class="px-3 py-3">
                                {{ $order->order_number }}
                            </td>

                            <td class="px-3 py-3">
                                {{ $order->customer_name }}
                            </td>

                            <td class="px-3 py-3">
                                ৳{{ number_format((float) $order->total, 2) }}
                            </td>

                            <td class="px-3 py-3 capitalize">
                                {{ $order->status }}
                            </td>

                            <td class="px-3 py-3">
                                {{ $order->created_at->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-gray-500">
                                No orders available yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <a href="{{ route('orders.manage') }}"
           class="inline-block rounded-md bg-indigo-600 px-5 py-2 text-white hover:bg-indigo-700">
            Manage Orders
        </a>

    </div>
</x-app-layout>
