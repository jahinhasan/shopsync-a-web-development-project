@if ($products->isNotEmpty())
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($products as $product)
            <article class="ss-motion-card ss-tilt-card overflow-hidden rounded-2xl border border-gray-200 bg-white"
                data-reveal>
                <a href="{{ route('shop.show', $product) }}" class="block overflow-hidden bg-gray-50">
                    @if ($product->image_path)
                        <img src="{{ \Illuminate\Support\Str::startsWith($product->image_path, ['http://', 'https://'])
                            ? $product->image_path
                            : asset('storage/' . $product->image_path) }}"
                            alt="{{ $product->name }}" loading="lazy"
                            class="ss-product-image h-40 w-full object-contain p-3 sm:h-48">
                    @else
                        <div class="flex h-40 items-center justify-center text-sm text-gray-400 sm:h-48">
                            No image
                        </div>
                    @endif
                </a>

                <div class="p-3 sm:p-4">
                    <p class="truncate text-xs text-gray-500">
                        {{ $product->category?->name ?? 'Uncategorized' }}
                    </p>

                    <h3 class="mt-1 line-clamp-2 min-h-10 text-sm font-semibold text-gray-900">
                        <a href="{{ route('shop.show', $product) }}" class="hover:text-indigo-600">
                            {{ $product->name }}
                        </a>
                    </h3>

                    @if ($product->owner)
                        <p class="mt-1 truncate text-xs text-gray-500">
                            Sold by {{ $product->owner->name }}
                        </p>
                    @endif
                    <div class="mt-2 flex items-center gap-2 text-sm">
                        @if (($product->rating_count ?? 0) > 0)
                            <span class="font-bold text-amber-500">
                                ★ {{ number_format((float) $product->rating_average, 1) }}
                            </span>
                            <span class="text-xs text-gray-500">
                                ({{ $product->rating_count }}
                                {{ \Illuminate\Support\Str::plural('rating', $product->rating_count) }})
                            </span>
                        @else
                            <span class="text-xs text-gray-400">Not rated yet</span>
                        @endif
                    </div>

                    <p class="mt-3 font-bold text-gray-900">
                        ৳{{ number_format((float) $product->price, 2) }}
                    </p>

                    <a href="{{ route('shop.show', $product) }}"
                        class="ss-action-button mt-3 flex w-full items-center justify-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 sm:text-sm">
                        View product
                    </a>
                </div>
            </article>
        @endforeach
    </div>
@else
    <p class="rounded-2xl border border-dashed border-gray-300 bg-white p-6 text-sm text-gray-500">
        No products available in this section yet.
    </p>
@endif
