<x-app-layout>
    <x-slot name="header">
        <div class="ss-page-heading">
            <p class="text-sm font-medium uppercase tracking-widest text-indigo-600">
                Discover something new
            </p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">
                Shop
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Explore products from our sellers.
            </p>
        </div>
    </x-slot>

    <section class="ss-shop-section mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Search and category filter toolbar --}}
        <div class="mb-5 flex flex-col gap-3">
            <form action="{{ route('shop.index') }}" method="GET" role="search"
                class="flex gap-2">
                @if ($categorySlug !== '')
                    <input type="hidden" name="category" value="{{ $categorySlug }}">
                @endif
                <div class="relative flex-1">
                    <label for="shop-search" class="sr-only">Search products</label>
                    <svg
                        aria-hidden="true"
                        class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <circle cx="11" cy="11" r="7" />
                        <path d="m20 20-3.5-3.5" />
                    </svg>
                    <input
                        id="shop-search"
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Search products, sellers or categories…"
                        autocomplete="off"
                        class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30"
                    >
                </div>
                <button type="submit"
                    class="shrink-0 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    Search
                </button>
            </form>

            <div class="flex flex-wrap items-center gap-2" role="group" aria-label="Filter by category">
                <a href="{{ route('shop.index', array_filter(['q' => $search])) }}" @class([
                    'rounded-full px-3.5 py-1.5 text-xs font-semibold transition',
                    'bg-indigo-600 text-white shadow-sm' => $categorySlug === '',
                    'bg-white text-gray-600 ring-1 ring-inset ring-gray-200 hover:bg-gray-50' => $categorySlug !== '',
                ])>
                    All products
                </a>
                @foreach ($categories as $category)
                    <a href="{{ route('shop.index', array_filter(['q' => $search, 'category' => $category->slug])) }}" @class([
                        'rounded-full px-3.5 py-1.5 text-xs font-semibold transition',
                        'bg-indigo-600 text-white shadow-sm' => $categorySlug === $category->slug,
                        'bg-white text-gray-600 ring-1 ring-inset ring-gray-200 hover:bg-gray-50' => $categorySlug !== $category->slug,
                    ])>
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>

        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-900">
                    @if ($search !== '')
                        Results for “{{ $search }}”
                    @elseif ($categorySlug !== '')
                        {{ $categories->firstWhere('slug', $categorySlug)?->name ?? 'Filtered' }} products
                    @else
                        Featured products
                    @endif
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $products->total() }} {{ \Illuminate\Support\Str::plural('product', $products->total()) }}
                    @if ($search !== '' || $categorySlug !== '')
                        match your filters
                    @else
                        available right now
                    @endif
                </p>
            </div>

            @if ($search !== '' || $categorySlug !== '')
                <a href="{{ route('shop.index') }}"
                    class="shrink-0 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                    Clear search &amp; filters
                </a>
            @endif
        </div>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @forelse ($products as $product)
                <article
                    class="ss-motion-card ss-tilt-card ss-product-card flex flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white"
                    data-reveal
                    data-reveal-delay="{{ ($loop->iteration - 1) % 4 }}"
                >
                    <a
                        href="{{ route('shop.show', $product) }}"
                        class="ss-product-image-frame block overflow-hidden bg-gray-50"
                        aria-label="View {{ $product->name }}"
                    >
                        @if ($product->image_path)
                            <img
                                src="{{ \Illuminate\Support\Str::startsWith($product->image_path, ['http://', 'https://'])
                                    ? $product->image_path
                                    : asset('storage/' . $product->image_path) }}"
                                alt="{{ $product->name }}"
                                loading="lazy"
                                class="ss-product-image h-40 w-full object-contain p-3 sm:h-48"
                            >
                        @else
                            <div class="flex h-40 items-center justify-center text-sm text-gray-400 sm:h-48">
                                No image
                            </div>
                        @endif
                    </a>

                    <div class="flex flex-1 flex-col p-3 sm:p-4">
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <span class="ss-category-label truncate rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                {{ $product->category?->name ?? 'Uncategorized' }}
                            </span>

                            @if ((int) $product->stock > 0)
                                <span class="shrink-0 text-xs font-medium text-emerald-700">
                                    In stock
                                </span>
                            @else
                                <span class="shrink-0 text-xs font-medium text-red-600">
                                    Out of stock
                                </span>
                            @endif
                        </div>

                        <h3 class="line-clamp-2 min-h-10 text-sm font-semibold text-gray-900">
                            <a
                                href="{{ route('shop.show', $product) }}"
                                class="ss-product-title-link"
                            >
                                {{ $product->name }}
                            </a>
                        </h3>

                        @if ($product->owner)
                            <p class="mt-1 truncate text-xs text-gray-500">
                                Sold by {{ $product->owner->name }}
                            </p>
                        @endif

                        <div class="mt-auto flex items-end justify-between gap-2 border-t border-gray-100 pt-3">
                            <p class="text-lg font-bold tracking-tight text-gray-900">
                                ৳{{ number_format((float) $product->price, 2) }}
                            </p>

                            <a
                                href="{{ route('shop.show', $product) }}"
                                class="ss-action-button inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white"
                            >
                                View
                                <svg
                                    aria-hidden="true"
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                >
                                    <path d="M5 12h14m-7-7 7 7-7 7" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                @if ($search !== '' || $categorySlug !== '')
                    <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
                        <h3 class="text-lg font-semibold text-gray-900">
                            No products match
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            @if ($search !== '')
                                Nothing found for “{{ $search }}”@if ($categorySlug !== '') in this category.@endif
                                Try a different keyword or spelling.
                            @else
                                No products are available in this category right now.
                            @endif
                        </p>
                        <a href="{{ route('shop.index') }}"
                            class="mt-4 inline-flex rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">
                            Browse all products
                        </a>
                    </div>
                @else
                    <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                        <h3 class="text-lg font-semibold text-gray-900">
                            No products available
                        </h3>
                        <p class="mt-2 text-sm text-gray-500">
                            Please check back later.
                        </p>
                    </div>
                @endif
            @endforelse
        </div>

        @if ($products->hasPages())
            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </section>
</x-app-layout>
