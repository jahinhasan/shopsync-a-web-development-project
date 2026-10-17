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
        <a href="{{ route('shop.index') }}"
            class="ss-back-link inline-flex items-center gap-2 text-sm font-medium text-indigo-600">
            <span aria-hidden="true">&larr;</span>
            Back to shop
        </a>

        <article
            class="ss-product-detail mt-6 grid gap-8 overflow-hidden rounded-3xl border border-gray-200 bg-white p-5 shadow-sm sm:p-8 md:grid-cols-2"
            data-reveal>
            <div
                class="ss-detail-image-panel flex min-h-72 items-center justify-center overflow-hidden rounded-2xl bg-gray-50 p-4 sm:min-h-96">
                @if ($product->image_path)
                    <img src="{{ \Illuminate\Support\Str::startsWith($product->image_path, ['http://', 'https://'])
                        ? $product->image_path
                        : asset('storage/' . $product->image_path) }}"
                        alt="{{ $product->name }}"
                        class="ss-product-image ss-detail-image max-h-96 w-full object-contain" fetchpriority="high">
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
                @auth
    @if (auth()->user()->isCustomer()
        && (int) auth()->id() !== (int) $product->user_id)
        <form method="POST"
              action="{{ route('chat.start', $product) }}"
              class="mt-3">
            @csrf

            <button type="submit"
                    class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-white px-6 py-3 font-semibold text-indigo-700 transition hover:border-indigo-400 hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 sm:w-auto">
                <svg aria-hidden="true"
                     class="h-5 w-5"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="1.8">
                    <path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8A8.5 8.5 0 0 1 8.7 3.9a8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"
                          stroke-linecap="round"
                          stroke-linejoin="round"/>
                </svg>
                Chat with seller
            </button>
        </form>
    @endif
@endauth

                <form method="POST" action="{{ route('cart.add', $product) }}" class="mt-7">
                    @csrf

                    <button type="submit" @disabled((int) $product->stock < 1)
                        class="ss-action-button inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white shadow-sm disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8">
                            <path d="M3 3h2l2.4 12.1a2 2 0 0 0 2 1.6h8.8a2 2 0 0 0 2-1.6L22 8H6" />
                            <circle cx="10" cy="20" r="1" />
                            <circle cx="18" cy="20" r="1" />
                        </svg>
                        {{ (int) $product->stock > 0 ? 'Add to Cart' : 'Out of Stock' }}
                    </button>
                </form>
            </div>
        </article>
        <section id="reviews" class="mt-10 scroll-mt-24">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)]">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-bold text-gray-900">Customer reviews</h2>

                    <div class="mt-3 flex items-center gap-2">
                        <span class="text-2xl font-bold text-gray-900">
                            {{ $ratingCount ? number_format($averageRating, 1) : '—' }}
                        </span>
                        <span class="text-lg tracking-wider text-amber-400" aria-label="Average rating">
                            @for ($star = 1; $star <= 5; $star++)
                                {{ $averageRating >= $star ? '★' : '☆' }}
                            @endfor
                        </span>
                    </div>

                    <p class="mt-1 text-sm text-gray-500">
                        {{ $ratingCount }} {{ \Illuminate\Support\Str::plural('rating', $ratingCount) }}
                    </p>

                    @if (session('success'))
                        <p role="status" class="mt-4 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">
                            {{ session('success') }}
                        </p>
                    @endif

                    @if ($errors->any())
                        <div role="alert" class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                            @foreach ($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    @auth
                        @if (auth()->user()->isCustomer())
                            <form method="POST" action="{{ route('shop.reviews.store', $product) }}"
                                class="mt-5 space-y-4">
                                @csrf

                                @if ($hasPurchased)
                                    <fieldset x-data="{ rating: Number(@js(old('rating', $userReview?->rating ?? 0))) }">
                                        <legend class="mb-2 text-sm font-semibold text-gray-700">
                                            Your rating
                                        </legend>

                                        <input type="hidden" name="rating" :value="rating || ''">

                                        <div class="flex items-center gap-1">
                                            @for ($star = 1; $star <= 5; $star++)
                                                <button type="button" @click="rating = {{ $star }}"
                                                    :aria-pressed="rating === {{ $star }}"
                                                    aria-label="{{ $star }} out of 5 stars"
                                                    :class="rating >= {{ $star }} ? 'text-amber-400' : 'text-gray-300'"
                                                    class="text-3xl transition duration-150 hover:scale-125 focus:outline-none">
                                                    ★
                                                </button>
                                            @endfor
                                        </div>

                                        <p class="mt-1 text-xs text-emerald-700">
                                            Verified purchase — rating is available.
                                        </p>
                                    </fieldset>
                                @else
                                    <p class="rounded-lg bg-indigo-50 p-3 text-sm text-indigo-700">
                                        You can comment now. You can rate this product after ordering it.
                                    </p>
                                @endif

                                <div>
                                    <label for="review-comment" class="mb-2 block text-sm font-semibold text-gray-700">
                                        Your comment
                                    </label>

                                    <textarea id="review-comment" name="comment" rows="4" maxlength="2000" required
                                        placeholder="Share your experience with this product..."
                                        class="w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('comment', $userReview?->comment) }}</textarea>
                                </div>

                                <button type="submit"
                                    class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    {{ $userReview ? 'Update your review' : 'Submit comment' }}
                                </button>
                            </form>
                        @endif
                    @else
                        <p class="mt-5 text-sm text-gray-600">
                            <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:underline">
                                Log in
                            </a>
                            to leave a comment.
                        </p>
                    @endauth
                </div>

                <div class="space-y-4">
                    <h2 class="text-xl font-bold text-gray-900">What customers say</h2>

                    @forelse ($reviews as $review)
                        <article
                            class="rounded-2xl border border-gray-200 bg-white p-5 transition duration-200 hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gray-900">
                                        {{ $review->user?->name ?? 'Customer' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ $review->created_at->format('M d, Y') }}
                                    </p>
                                </div>

                                @if ($review->rating)
                                    <span class="text-amber-400" aria-label="{{ $review->rating }} out of 5 stars">
                                        @for ($star = 1; $star <= 5; $star++)
                                            {{ $star <= $review->rating ? '★' : '☆' }}
                                        @endfor
                                    </span>
                                @else
                                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-600">
                                        Comment
                                    </span>
                                @endif
                            </div>

                            <p class="mt-4 whitespace-pre-line break-words text-sm leading-6 text-gray-700">
                                {{ $review->comment }}</p>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-300 p-8 text-center">
                            <p class="font-semibold text-gray-700">No reviews yet</p>
                            <p class="mt-2 text-sm text-gray-500">
                                Be the first to share your experience.
                            </p>
                        </div>
                    @endforelse

                    {{ $reviews->fragment('reviews')->links() }}
                </div>
            </div>
        </section>
    </section>
</x-app-layout>
