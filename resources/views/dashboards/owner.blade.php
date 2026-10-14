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

            {{-- Today's performance --}}
            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Products', $totalProducts, 'Products in your catalogue', '📦'],
                    ['Available Stock', number_format($totalStock), 'Units across your products', '🏷️'],
                    ['Total Orders', number_format($totalOrders), 'Orders containing your products', '🛍️'],
                    ['Recorded Sales', '৳'.number_format((float) $totalSales, 2), 'Based on qualifying order statuses', '৳'],
                ] as [$title, $value, $description, $symbol])
                    <article class="ss-motion-card rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm"
                             data-reveal>
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-medium text-gray-500">
                                    {{ $title }}
                                </p>
                                <p class="mt-3 break-words text-2xl font-bold tracking-tight text-gray-900">
                                    {{ $value }}
                                </p>
                            </div>

                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-xl text-indigo-700">
                                {{ $symbol }}
                            </span>
                        </div>

                        <p class="mt-3 text-xs leading-5 text-gray-500">
                            {{ $description }}
                        </p>
                    </article>
                @endforeach
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

                <article class="rounded-2xl border border-amber-200 bg-amber-50 p-5" data-reveal>
                    <p class="text-sm font-medium text-amber-800">Pending / Processing</p>
                    <p class="mt-2 text-2xl font-bold text-amber-950">
                        {{ number_format($pendingOrders) }}
                    </p>
                    <p class="mt-1 text-xs text-amber-800">Orders that may need attention</p>
                </article>
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
