
<x-app-layout>
    <x-slot name="header">
        <p class="text-sm font-semibold text-indigo-600">ShopSync Administration</p>
        <h1 class="mt-1 text-2xl font-bold text-gray-900">Moderation Center</h1>
        <p class="mt-1 text-sm text-gray-500">
            Manage product visibility, issue warnings, and control shop access.
        </p>
    </x-slot>

    <div class="min-h-screen space-y-10 bg-gray-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-10">

            @if (session('success'))
                <div role="status" class="rounded-xl bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{-- PRODUCT MODERATION --}}
            <section class="space-y-5">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Product moderation</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Suspend products, warn owners, or remove products from the customer store.
                    </p>
                </div>

                <form method="GET"
                      action="{{ route('admin.moderation.index') }}"
                      class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 sm:flex-row">
                    <input
                        type="search"
                        name="product_search"
                        value="{{ request('product_search') }}"
                        placeholder="Search product name or slug"
                        class="min-w-0 flex-1 rounded-xl border-gray-300"
                    >

                    <select name="product_status" class="rounded-xl border-gray-300 sm:w-48">
                        <option value="">All products</option>
                        <option value="active" @selected(request('product_status') === 'active')>Active</option>
                        <option value="suspended" @selected(request('product_status') === 'suspended')>Suspended</option>
                        <option value="removed" @selected(request('product_status') === 'removed')>Removed</option>
                    </select>

                    <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">
                        Search
                    </button>

                    <a href="{{ route('admin.moderation.index') }}"
                       class="rounded-xl border border-gray-200 px-5 py-2.5 text-center text-sm font-semibold text-gray-700">
                        Reset
                    </a>
                </form>

                <div class="space-y-4">
                    @forelse ($products as $product)
                        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">
                                        {{ $product->name }}
                                    </h3>

                                    <p class="mt-1 text-sm text-gray-500">
                                        Product ID: {{ $product->id }}
                                        · Owner: {{ $product->owner?->name ?? 'Account unavailable' }}
                                        · {{ $product->category?->name ?? 'Uncategorized' }}
                                    </p>

                                    <p class="mt-2 text-sm text-gray-700">
                                        Price: ৳{{ number_format((float) $product->price, 2) }}
                                        · Stock: {{ $product->stock }}
                                    </p>

                                    @if ($product->admin_reason)
                                        <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                                            <strong>Admin reason:</strong>
                                            {{ $product->admin_reason }}
                                        </p>
                                    @endif
                                </div>

                                <span class="rounded-full px-3 py-1 text-xs font-bold
                                    {{ $product->trashed()
                                        ? 'bg-red-50 text-red-700'
                                        : ($product->admin_status === 'suspended'
                                            ? 'bg-amber-50 text-amber-800'
                                            : 'bg-green-50 text-green-700') }}">
                                    {{ $product->trashed() ? 'Removed' : ucfirst($product->admin_status) }}
                                </span>
                            </div>

                            <div class="mt-5 grid gap-4 border-t border-gray-100 pt-5 lg:grid-cols-3">

                                {{-- Suspend --}}
                                @if (!$product->trashed() && $product->admin_status !== 'suspended')
                                    <form method="POST"
                                          action="{{ route('admin.moderation.products.suspend', $product->id) }}"
                                          class="space-y-2 rounded-xl border border-amber-200 p-4">
                                        @csrf
                                        @method('PATCH')

                                        <h4 class="font-semibold text-gray-900">Suspend product</h4>

                                        <textarea name="reason" required minlength="5" maxlength="2000"
                                                  rows="3"
                                                  class="w-full rounded-lg border-gray-300 text-sm"
                                                  placeholder="Explain the suspension reason"></textarea>

                                        <button class="w-full rounded-lg bg-amber-600 px-3 py-2 text-sm font-semibold text-white">
                                            Suspend
                                        </button>
                                    </form>
                                @endif

                                {{-- Warn owner --}}
                                @if (!$product->trashed())
                                    <form method="POST"
                                          action="{{ route('admin.moderation.products.warn', $product->id) }}"
                                          class="space-y-2 rounded-xl border border-blue-200 p-4">
                                        @csrf
                                        @method('PATCH')

                                        <h4 class="font-semibold text-gray-900">Warn owner</h4>

                                        <textarea name="warning" required minlength="5" maxlength="2000"
                                                  rows="3"
                                                  class="w-full rounded-lg border-gray-300 text-sm"
                                                  placeholder="Write a clear warning for the owner"></textarea>

                                        <button class="w-full rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white">
                                            Send warning
                                        </button>
                                    </form>
                                @endif

                                {{-- Remove or restore --}}
                                <div class="space-y-3 rounded-xl border border-gray-200 p-4">
                                    @if ($product->trashed())
                                        <h4 class="font-semibold text-gray-900">Restore product</h4>

                                        <p class="text-sm text-gray-500">
                                            Restore its database record. It may still need owner activation.
                                        </p>

                                        <form method="POST"
                                              action="{{ route('admin.moderation.products.restore', $product->id) }}"
                                              onsubmit="return confirm('Restore this product?');">
                                            @csrf
                                            @method('PATCH')

                                            <button class="w-full rounded-lg bg-green-700 px-3 py-2 text-sm font-semibold text-white">
                                                Restore
                                            </button>
                                        </form>
                                    @else
                                        <h4 class="font-semibold text-gray-900">Remove product</h4>

                                        <form method="POST"
                                              action="{{ route('admin.moderation.products.remove', $product->id) }}"
                                              class="space-y-2"
                                              onsubmit="return confirm('Remove this product from the customer store?');">
                                            @csrf
                                            @method('DELETE')

                                            <textarea name="reason" required minlength="5" maxlength="2000"
                                                      rows="3"
                                                      class="w-full rounded-lg border-gray-300 text-sm"
                                                      placeholder="Removal reason"></textarea>

                                            <button class="w-full rounded-lg bg-red-700 px-3 py-2 text-sm font-semibold text-white">
                                                Remove product
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                            No products found.
                        </div>
                    @endforelse
                </div>

                {{ $products->links() }}
            </section>

            {{-- SHOP MODERATION --}}
            <section class="space-y-5">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Shop moderation</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Suspend a shop temporarily, close it, or reactivate it.
                    </p>
                </div>

                <div class="space-y-4">
                    @forelse ($shops as $shop)
                        <article class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
                            <div class="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900">
                                        {{ $shop->shop_name }}
                                    </h3>

                                    <p class="mt-1 text-sm text-gray-500">
                                        Application #{{ $shop->id }}
                                        · Application status: {{ ucfirst($shop->status) }}
                                    </p>

                                    @if ($shop->shop_status_reason)
                                        <p class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                                            <strong>Latest reason:</strong>
                                            {{ $shop->shop_status_reason }}
                                        </p>
                                    @endif
                                </div>

                                <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700">
                                    {{ ucfirst($shop->shop_status) }}
                                </span>
                            </div>

                            <form method="POST"
                                  action="{{ route('admin.moderation.shops.status', $shop->id) }}"
                                  class="mt-5 grid gap-3 border-t border-gray-100 pt-5 sm:grid-cols-[180px_1fr_auto]"
                                  onsubmit="return confirm('Confirm this shop status change?');">
                                @csrf
                                @method('PATCH')

                                <select name="action" required class="rounded-xl border-gray-300 text-sm">
                                    <option value="suspend">Suspend shop</option>
                                    <option value="close">Close shop</option>
                                    <option value="reactivate">Reactivate shop</option>
                                </select>

                                <input name="reason" required minlength="5" maxlength="2000"
                                       class="rounded-xl border-gray-300 text-sm"
                                       placeholder="Reason for this status change">

                                <button class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white">
                                    Apply action
                                </button>
                            </form>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500">
                            No shop applications found.
                        </div>
                    @endforelse
                </div>

                {{ $shops->links() }}
            </section>
        </div>
    </div>
</x-app-layout>
