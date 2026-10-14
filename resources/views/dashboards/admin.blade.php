<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">
                    ShopSync Control Center
                </p>
                <h2 class="mt-1 text-xl font-bold text-gray-900">
                    Admin Dashboard
                </h2>
            </div>

            <a href="{{ route('admin.reports') }}"
               class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                View Analytics →
            </a>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-50/80 py-8">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">

            {{-- Welcome --}}
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-indigo-900 to-indigo-700 p-6 text-white shadow-lg sm:p-8"
                     data-reveal>
                <div class="relative z-10 flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
                    <div>
                        <p class="text-sm font-semibold text-indigo-200">
                            Platform overview
                        </p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">
                            Welcome, {{ auth()->user()->name }}
                        </h1>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-indigo-100 sm:text-base">
                            Monitor ShopSync activity, review orders and keep the marketplace running smoothly.
                        </p>
                    </div>

                    <a href="{{ route('admin.categories.index') }}"
                       class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-bold text-indigo-800 transition hover:-translate-y-0.5 hover:bg-indigo-50">
                        Manage Categories
                    </a>
                </div>

                <div class="pointer-events-none absolute -right-12 -top-20 h-64 w-64 rounded-full border-[32px] border-white/10"></div>
                <div class="pointer-events-none absolute -bottom-24 right-40 h-48 w-48 rounded-full bg-white/5"></div>
            </section>

            {{-- Platform statistics --}}
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ([
                    ['Total Users', number_format($totalUsers), 'Registered accounts', '👥'],
                    ['Total Products', number_format($totalProducts), 'Marketplace catalogue', '📦'],
                    ['Categories', number_format($totalCategories), 'Product classifications', '🏷️'],
                    ['Total Orders', number_format($totalOrders), 'All recorded orders', '🛍️'],
                    ['Processed Sales', '৳'.number_format((float) $totalSales, 2), 'Processing, shipped and delivered orders', '৳'],
                    ['Low-Stock Products', number_format($lowStockProducts->count()), 'Showing up to 8 products with stock ≤ 5', '⚠️'],
                ] as [$title, $value, $description, $symbol])
                    <article class="ss-motion-card rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm"
                             data-reveal>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
                                <p class="mt-3 break-words text-2xl font-bold tracking-tight text-gray-900">
                                    {{ $value }}
                                </p>
                            </div>
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xl">
                                {{ $symbol }}
                            </span>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-gray-500">{{ $description }}</p>
                    </article>
                @endforeach
            </section>

            {{-- User roles --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6"
                     data-reveal>
                <h3 class="text-lg font-bold text-gray-900">User Accounts</h3>
                <p class="mt-1 text-sm text-gray-500">Account counts grouped by assigned role.</p>

                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        ['Customers', 'customer'],
                        ['Business Owners', 'business_owner'],
                        ['Administrators', 'admin'],
                    ] as [$label, $role])
                        <div class="rounded-xl bg-gray-50 p-4">
                            <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
                            <p class="mt-2 text-2xl font-bold text-gray-900">
                                {{ number_format((int) ($roleCounts[$role] ?? 0)) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Recent orders --}}
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                     data-reveal>
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 p-5 sm:p-6">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">Recent Orders</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            The latest orders recorded across the marketplace.
                        </p>
                    </div>
                    <a href="{{ route('admin.reports') }}"
                       class="text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        View Reports →
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
                                <th class="px-5 py-4 text-right font-semibold">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($recentOrders as $order)
                                @php
                                    $statusClasses = match (strtolower((string) $order->status)) {
                                        'pending' => 'bg-amber-100 text-amber-800',
                                        'processing' => 'bg-blue-100 text-blue-800',
                                        'shipped' => 'bg-violet-100 text-violet-800',
                                        'delivered' => 'bg-green-100 text-green-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp

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
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClasses }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-gray-900">
                                        ৳{{ number_format((float) $order->total, 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-12 text-center text-gray-500">
                                        No orders have been placed yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Low stock --}}
            <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                     data-reveal>
                <div class="border-b border-gray-100 p-5 sm:p-6">
                    <h3 class="text-lg font-bold text-gray-900">Low-Stock Alerts</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Products with five or fewer units remaining.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[500px] text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-4 font-semibold">Product</th>
                                <th class="px-5 py-4 font-semibold">Business Owner</th>
                                <th class="px-5 py-4 text-right font-semibold">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($lowStockProducts as $product)
                                <tr class="transition hover:bg-gray-50">
                                    <td class="px-5 py-4 font-semibold text-gray-900">
                                        {{ $product->name }}
                                    </td>
                                    <td class="px-5 py-4 text-gray-600">
                                        {{ $product->owner?->name ?? 'Unknown' }}
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold {{ $product->stock <= 0 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-800' }}">
                                            {{ $product->stock <= 0 ? 'Out of stock' : $product->stock.' left' }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-10 text-center text-gray-500">
                                        No low-stock products found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- Quick actions --}}
            <section data-reveal>
                <div class="mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Administration</h3>
                    <p class="mt-1 text-sm text-gray-500">Shortcuts to key platform sections.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        ['Category Management', 'Create, edit and manage product categories.', route('admin.categories.index'), '🏷️'],
                        ['Sales & Analytics', 'Review sales, trends and best-selling products.', route('admin.reports'), '📈'],
                        ['Customer Storefront', 'Inspect the customer-facing product catalogue.', route('shop.index'), '🛒'],
                    ] as [$title, $description, $url, $icon])
                        <a href="{{ $url }}"
                           class="ss-motion-card group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300"
                           data-reveal>
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-xl transition group-hover:bg-indigo-100">
                                {{ $icon }}
                            </span>
                            <h4 class="mt-4 font-bold text-gray-900">{{ $title }}</h4>
                            <p class="mt-2 text-sm leading-6 text-gray-500">{{ $description }}</p>
                            <span class="mt-4 inline-block text-sm font-semibold text-indigo-600">
                                Open section →
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
