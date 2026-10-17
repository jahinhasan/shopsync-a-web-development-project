<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-indigo-600">
                    ShopSync marketplace
                </p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">
                    Welcome, {{ auth()->user()->name }}
                </h1>
            </div>

            <a href="{{ route('orders.index') }}"
                class="ss-action-button inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700">
                My orders <span aria-hidden="true">→</span>
            </a>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-50">
        <div class="mx-auto max-w-7xl space-y-10 px-4 py-7 sm:px-6 lg:px-8">

            @if (session('success'))
                <div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            {{-- Search and quick links --}}
            <section
                class="rounded-3xl bg-gradient-to-br from-indigo-950 via-indigo-800 to-violet-700 p-6 text-white shadow-lg sm:p-9">
                <p class="text-sm font-semibold text-indigo-200">
                    YOUR NEXT GREAT FIND STARTS HERE
                </p>

                <h2 class="mt-3 max-w-2xl text-3xl font-extrabold tracking-tight sm:text-4xl">
                    Find something you'll love.
                </h2>

                <p class="mt-3 max-w-xl text-sm leading-6 text-indigo-100 sm:text-base">
                    Explore products from ShopSync sellers, discover new categories and shop your way.
                </p>

                <form action="{{ route('shop.index') }}" method="GET"
                    class="mt-6 flex max-w-2xl flex-col gap-2 rounded-2xl bg-white p-2 sm:flex-row">
                    <label for="customer-search" class="sr-only">Search products</label>
                    <input id="customer-search" type="search" name="q"
                        placeholder="Search products, categories or shops..."
                        class="min-w-0 flex-1 rounded-xl border-0 px-4 py-3 text-sm text-gray-900 outline-none ring-0 placeholder:text-gray-400 focus:border-0 focus:ring-0">
                    <button type="submit"
                        class="ss-action-button rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white hover:bg-indigo-700">
                        Search products
                    </button>
                </form>

                <div class="mt-5 flex flex-wrap gap-3 text-sm">
                    <a href="{{ route('cart.index') }}"
                        class="rounded-lg border border-white/20 bg-white/10 px-4 py-2 transition hover:bg-white/20">
                        Shopping cart
                    </a>
                    <a href="{{ route('orders.index') }}"
                        class="rounded-lg border border-white/20 bg-white/10 px-4 py-2 transition hover:bg-white/20">
                        Track my orders
                    </a>
                </div>
            </section>

            {{-- Shop by category dropdown filter --}}
            <section>
                <form action="{{ route('shop.index') }}" method="GET"
                    class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end">

                    <div class="flex-1">
                        <label for="customer-category" class="mb-2 block text-sm font-bold text-gray-900">
                            Shop by Category
                        </label>

                        <select id="customer-category" name="category"
                            class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Categories</option>

                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}">
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit"
                        class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-indigo-700">
                        Filter Products
                    </button>
                </form>
            </section>

            {{-- Featured products --}}
            <section>
                <div class="mb-5 flex items-end justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">
                            Customer favourites
                        </p>
                        <h2 class="mt-1 text-2xl font-bold text-gray-900">
                            Top-rated products
                        </h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Products ranked by customer ratings and review count.
                        </p>
                    </div>
                    <a href="{{ route('shop.index') }}"
                        class="shrink-0 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                        View all →
                    </a>
                </div>

                @include('dashboards.partials.product-grid', ['products' => $featuredProducts])
            </section>

            {{-- Category-specific product rows --}}
            @foreach ($categories as $category)
                <section>
                    <div class="mb-5 flex items-end justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">Browse by category
                            </p>
                            <h2 class="mt-1 text-2xl font-bold text-gray-900">{{ $category->name }}</h2>
                        </div>
                        <a href="{{ route('shop.index', ['category' => $category->slug]) }}"
                            class="shrink-0 text-sm font-semibold text-indigo-600 hover:text-indigo-800">
                            See more →
                        </a>
                    </div>

                    @include('dashboards.partials.product-grid', ['products' => $category->products])
                </section>
            @endforeach

            {{-- New arrivals --}}
            <section>
                <div class="mb-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">Recently added</p>
                    <h2 class="mt-1 text-2xl font-bold text-gray-900">New arrivals</h2>
                </div>

                @include('dashboards.partials.product-grid', ['products' => $newArrivals])
            </section>

            {{-- Top shops based on active product count --}}
            <section>
                <div class="mb-5">
                    <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">Marketplace sellers</p>
                    <h2 class="mt-1 text-2xl font-bold text-gray-900">Shops to explore</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Sellers ranked by the number of currently available products.
                    </p>
                </div>

                @if ($topShops->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($topShops as $shop)
                            <a href="{{ route('shop.index', ['q' => $shop->name]) }}"
                                class="ss-motion-card flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 hover:border-indigo-300"
                                data-reveal>
                                <span
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-lg font-bold text-white">
                                    {{ strtoupper(mb_substr($shop->name, 0, 1)) }}
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-bold text-gray-900">{{ $shop->name }}</span>
                                    <span class="mt-1 block text-sm text-gray-500">
                                        {{ $shop->product_count }} available
                                        {{ (int) $shop->product_count === 1 ? 'product' : 'products' }}
                                    </span>
                                </span>
                                <span class="text-indigo-600" aria-hidden="true">→</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500">
                        No sellers have available products yet.
                    </p>
                @endif
            </section>

            {{-- Account shortcuts --}}
            <section class="grid gap-4 border-t border-gray-200 pt-7 sm:grid-cols-3">
                <a href="{{ route('cart.index') }}"
                    class="ss-motion-card rounded-2xl border border-gray-200 bg-white p-5">
                    <h3 class="font-bold text-gray-900">Shopping cart</h3>
                    <p class="mt-2 text-sm text-gray-500">Review your items and quantities.</p>
                    <span class="mt-4 inline-block text-sm font-semibold text-indigo-600">Open cart →</span>
                </a>
                <a href="{{ route('orders.index') }}"
                    class="ss-motion-card rounded-2xl border border-gray-200 bg-white p-5">
                    <h3 class="font-bold text-gray-900">Your orders</h3>
                    <p class="mt-2 text-sm text-gray-500">Review previous purchases and statuses.</p>
                    <span class="mt-4 inline-block text-sm font-semibold text-indigo-600">Order history →</span>
                </a>
                <a href="{{ route('profile.edit') }}"
                    class="ss-motion-card rounded-2xl border border-gray-200 bg-white p-5">
                    <h3 class="font-bold text-gray-900">Your account</h3>
                    <p class="mt-2 text-sm text-gray-500">Manage your profile and account settings.</p>
                    <span class="mt-4 inline-block text-sm font-semibold text-indigo-600">Edit profile →</span>
                </a>
            </section>
        </div>
    </div>
</x-app-layout>
