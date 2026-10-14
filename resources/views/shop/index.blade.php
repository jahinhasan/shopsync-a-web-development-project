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
        <div class="mb-7 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold tracking-tight text-gray-900">
                    Featured products
                </h2>
                <p class="mt-1 text-sm text-gray-500">
                    Find the right product for you.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($products as $product)
                <article
                    class="ss-motion-card ss-tilt-card ss-product-card overflow-hidden rounded-2xl border border-gray-200 bg-white"
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
                                class="ss-product-image h-60 w-full object-contain p-5"
                            >
                        @else
                            <div class="flex h-60 items-center justify-center text-gray-400">
                                <svg
                                    aria-hidden="true"
                                    class="h-14 w-14"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.3"
                                >
                                    <rect x="3" y="3" width="18" height="18" rx="2" />
                                    <circle cx="8.5" cy="8.5" r="1.5" />
                                    <path d="m21 15-5-5L5 21" />
                                </svg>
                            </div>
                        @endif
                    </a>

                    <div class="p-5">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <span class="ss-category-label rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                                {{ $product->category?->name ?? 'Uncategorized' }}
                            </span>

                            @if ((int) $product->stock > 0)
                                <span class="text-xs font-medium text-emerald-700">
                                    In stock
                                </span>
                            @else
                                <span class="text-xs font-medium text-red-600">
                                    Out of stock
                                </span>
                            @endif
                        </div>

                        <h3 class="line-clamp-2 min-h-12 text-lg font-semibold text-gray-900">
                            <a
                                href="{{ route('shop.show', $product) }}"
                                class="ss-product-title-link"
                            >
                                {{ $product->name }}
                            </a>
                        </h3>

                        @if ($product->owner)
                            <p class="mt-2 flex items-center gap-2 text-sm text-gray-500">
                                <svg
                                    aria-hidden="true"
                                    class="h-4 w-4 shrink-0"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="1.7"
                                >
                                    <path d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M15 10h.01M9 14h.01M15 14h.01M10 21v-4h4v4" />
                                </svg>
                                <span class="truncate">
                                    Sold by {{ $product->owner->name }}
                                </span>
                            </p>
                        @endif

                        <div class="mt-5 flex items-end justify-between gap-3 border-t border-gray-100 pt-4">
                            <div>
                                <p class="text-xs text-gray-500">Price</p>
                                <p class="mt-1 text-xl font-bold tracking-tight text-gray-900">
                                    ৳{{ number_format((float) $product->price, 2) }}
                                </p>
                            </div>

                            <a
                                href="{{ route('shop.show', $product) }}"
                                class="ss-action-button inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white"
                            >
                                View product
                                <svg
                                    aria-hidden="true"
                                    class="h-4 w-4"
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
                <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        No products available
                    </h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Please check back later.
                    </p>
                </div>
            @endforelse
        </div>

        @if ($products->hasPages())
            <div class="mt-8">
                {{ $products->links() }}
            </div>
        @endif
    </section>
</x-app-layout>
