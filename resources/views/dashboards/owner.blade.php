<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Business Owner Dashboard
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">

            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    Welcome, {{ auth()->user()->name }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Manage your store, inventory, orders and sales.
                </p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Products', $totalProducts],
                    ['Available Stock', $totalStock],
                    ['Orders', $totalOrders],
                    ['Sales', '৳' . number_format((float) $totalSales, 2)],
                ] as [$title, $value])
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <p class="text-sm text-gray-500">{{ $title }}</p>
                        <h3 class="mt-2 text-2xl font-bold text-gray-900">
                            {{ $value }}
                        </h3>
                    </div>
                @endforeach
            </div>

            <div>
                <h3 class="mb-4 text-lg font-semibold text-gray-900">
                    Store Management
                </h3>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <a href="{{ route('owner.products.index') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">Manage Products</h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Add, edit, delete and manage your products.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            View Products →
                        </span>
                    </a>

                    <a href="{{ route('owner.order.manage') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">Manage Orders</h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Review orders containing your products and update status.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            View Orders →
                        </span>
                    </a>

                    <a href="{{ route('owner.inventory.history') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">Inventory History</h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Track stock additions, reductions and sales.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            View History →
                        </span>
                    </a>

                    <a href="{{ route('owner.reports') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">Sales & Analytics</h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Review sales, best-selling products and low stock.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            View Reports →
                        </span>
                    </a>

                    <a href="{{ route('shop.index') }}"
                       class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-500 hover:bg-indigo-50">
                        <h4 class="font-semibold text-gray-900">View Storefront</h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Preview the customer-facing product catalogue.
                        </p>
                        <span class="mt-4 inline-block text-sm font-medium text-indigo-600">
                            Open Store →
                        </span>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>