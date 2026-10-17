<x-app-layout>
    <x-slot name="header">
        <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">
            ShopSync Rewards
        </p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Points & Vouchers</h1>
    </x-slot>

    <div class="min-h-screen bg-gray-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl space-y-8">

            @if (session('success'))
                <div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-950 via-indigo-800 to-violet-700 p-7 text-white shadow-lg sm:p-10">
                <p class="text-sm font-semibold text-indigo-200">YOUR REWARDS WALLET</p>
                <h2 class="mt-3 text-4xl font-extrabold">
                    {{ number_format($pointsBalance) }}
                    <span class="text-xl font-semibold text-indigo-200">points</span>
                </h2>
                <p class="mt-3 max-w-xl text-sm leading-6 text-indigo-100">
                    Earn points when eligible orders are delivered and payment is confirmed.
                    Use points to obtain vouchers for eligible products.
                </p>
            </section>

            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-xl font-bold text-gray-900">Redeem 100 points</h2>
                <p class="mt-2 text-sm text-gray-500">
                    Get a 5% voucher on a selected category, up to ৳100 discount.
                    The eligible products subtotal must be at least ৳500.
                </p>

                <form method="POST" action="{{ route('customer.rewards.buy-voucher') }}"
                      class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf

                    <div class="flex-1">
                        <label for="reward-category" class="mb-2 block text-sm font-semibold text-gray-800">
                            Eligible category
                        </label>

                        <select id="reward-category" name="category_id" required
                                class="w-full rounded-xl border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Choose a category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                        @selected((string) old('category_id') === (string) $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit"
                            @disabled($pointsBalance < 100 || $categories->isEmpty())
                            class="rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                        Get voucher — 100 points
                    </button>
                </form>
            </section>

            <section>
                <div class="mb-4">
                    <h2 class="text-xl font-bold text-gray-900">My vouchers</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Surprise vouchers are issued after eligible purchases qualify for rewards.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    @forelse ($vouchers as $customerVoucher)
                        @php
                            $voucher = $customerVoucher->voucher;
                            $expired = !$customerVoucher->expires_at
                                || $customerVoucher->expires_at->isPast();
                        @endphp

                        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">
                                        {{ $customerVoucher->source === 'surprise' ? 'Surprise voucher' : 'Points voucher' }}
                                    </p>
                                    <h3 class="mt-2 font-bold text-gray-900">
                                        {{ $voucher?->name ?? 'Voucher' }}
                                    </h3>
                                </div>

                                <span class="rounded-full px-3 py-1 text-xs font-bold
                                    {{ $customerVoucher->status === 'available' && !$expired
                                        ? 'bg-emerald-50 text-emerald-700'
                                        : 'bg-gray-100 text-gray-600' }}">
                                    {{ $customerVoucher->status === 'available' && $expired
                                        ? 'Expired'
                                        : ucfirst($customerVoucher->status) }}
                                </span>
                            </div>

                            @if ($voucher)
                                <p class="mt-3 text-2xl font-extrabold text-indigo-700">
                                    @if ($voucher->discount_type === 'percent')
                                        {{ rtrim(rtrim(number_format((float) $voucher->discount_value, 2), '0'), '.') }}% OFF
                                    @else
                                        ৳{{ number_format((float) $voucher->discount_value, 2) }} OFF
                                    @endif
                                </p>

                                <p class="mt-2 text-sm text-gray-500">
                                    Category: {{ $voucher->category?->name ?? 'Specific product' }}
                                </p>

                                <p class="mt-1 text-sm text-gray-500">
                                    Min. eligible subtotal ৳{{ number_format((float) $voucher->min_order_total, 2) }}
                                    · Max. discount ৳{{ number_format((float) $voucher->max_discount, 2) }}
                                </p>
                            @endif

                            <div class="mt-4 rounded-xl border border-dashed border-indigo-200 bg-indigo-50 p-3">
                                <p class="text-xs font-semibold text-indigo-600">VOUCHER CODE</p>
                                <p class="mt-1 break-all font-mono text-lg font-bold tracking-wider text-indigo-900">
                                    {{ $customerVoucher->code }}
                                </p>
                                <button type="button"
                                        onclick="navigator.clipboard.writeText(@js($customerVoucher->code))"
                                        class="mt-2 text-xs font-semibold text-indigo-700 hover:underline">
                                    Copy code
                                </button>
                            </div>

                            <p class="mt-3 text-xs text-gray-500">
                                Expires:
                                {{ $customerVoucher->expires_at?->format('M d, Y') ?? 'Not set' }}
                            </p>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-sm text-gray-500 md:col-span-2">
                            You do not have any vouchers yet. Complete eligible orders or redeem points to receive one.
                        </div>
                    @endforelse
                </div>

                <div class="mt-6">
                    {{ $vouchers->links() }}
                </div>
            </section>
        </div>
    </div>
</x-app-layout>