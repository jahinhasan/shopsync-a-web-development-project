<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-widest text-indigo-600">
                Explore the details
            </p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">
                Product details
            </h1>
        </div>
    </x-slot>

    <section class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <a
            href="{{ route('shop.index') }}"
            class="ss-back-link inline-flex items-center gap-2 text-sm font-medium text-indigo-600"
        >
            <span aria-hidden="true">&larr;</span>
            Back to shop
        </a>

        <article
            class="ss-product-detail mt-6 grid gap-8 overflow-hidden rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8 md:grid-cols-2"
            data-reveal
        >
            <div class="ss-detail-image-panel flex min-h-72 items-center justify-center overflow-hidden rounded-2xl bg-gray-50 p-4 sm:min-h-96">
                @if ($product->image_path)
                    <img
                        src="{{ \Illuminate\Support\Str::startsWith($product->image_path, ['http://', 'https://'])
                            ? $product->image_path
                            : asset('storage/' . $product->image_path) }}"
                        alt="{{ $product->name }}"
                        class="ss-product-image ss-detail-image max-h-96 w-full object-contain"
                        fetchpriority="high"
                    >
                @else
                    <div class="flex min-h-72 items-center justify-center text-gray-400">
                        <span>No product image available</span>
                    </div>
                @endif
            </div>

            <div class="flex flex-col justify-center py-2 sm:py-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
                        {{ $product->category?->name ?? 'Uncategorized' }}
                    </span>

                    @if ((int) $product->stock > 0)
                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                            In stock
                        </span>
                    @else
                        <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                            Out of stock
                        </span>
                    @endif
                </div>

                <h2 class="mt-5 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">
                    {{ $product->name }}
                </h2>

                @if ($product->owner)
                    <p class="mt-3 text-sm text-gray-500">
                        Sold by
                        <span class="font-semibold text-gray-800">
                            {{ $product->owner->name }}
                        </span>
                    </p>
                @endif

                <p class="mt-5 text-3xl font-bold tracking-tight text-indigo-700">
                    ৳{{ number_format((float) $product->price, 2) }}
                </p>

                <div class="ss-description mt-6 leading-7 text-gray-600">
                    {{ $product->description ?: 'No description available.' }}
                </div>

                <div class="mt-6 border-t border-gray-100 pt-5">
                    <p class="text-sm text-gray-500">Available quantity</p>
                    <p class="mt-1 font-semibold text-gray-900">
                        {{ $product->stock }} units
                    </p>
                </div>

                @if (session('success'))
                    <div role="status" class="mt-4 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-800">
                        {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div role="alert" class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800">
                        {{ session('error') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div role="alert" class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-800">
                        <ul class="list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('cart.add', $product) }}" class="mt-7">
                    @csrf

                    <button
                        type="submit"
                        @disabled((int) $product->stock < 1)
                        class="ss-action-button inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                    >
                        <svg
                            aria-hidden="true"
                            class="h-5 w-5"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                        >
                            <path d="M3 3h2l2.4 12.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 8H6" />
                            <circle cx="10" cy="20" r="1" />
                            <circle cx="18" cy="20" r="1" />
                        </svg>
                        {{ (int) $product->stock > 0 ? 'Add to Cart' : 'Out of Stock' }}
                    </button>
                </form>
            </div>
        </article>
    </section>
</x-app-layout>
