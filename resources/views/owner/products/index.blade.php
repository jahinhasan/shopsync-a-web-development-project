
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Products
        </h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8">

        <div>
            <a href="{{ route('owner.products.create') }}"
               class="inline-block rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                Add Product
            </a>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-100 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-100 px-4 py-3 text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Product Search --}}
        <form method="GET"
              action="{{ route('owner.products.index') }}"
              class="flex flex-wrap items-end gap-3 rounded-lg border bg-white p-4">

            <div>
                <label for="search" class="mb-1 block text-sm font-medium text-gray-700">
                    Search Product
                </label>

                <input
                    id="search"
                    type="text"
                    name="search"
                    placeholder="Product name or slug"
                    value="{{ request('search') }}"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>

            <button type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                Search
            </button>

            <a href="{{ route('owner.products.index') }}"
               class="rounded-md border border-gray-300 px-4 py-2 text-gray-700 hover:bg-gray-50">
                Reset
            </a>
        </form>

        {{-- Products Table --}}
        <div class="overflow-x-auto rounded-lg border bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100 text-gray-700">
                    <tr>
                        <th class="p-3">Image</th>
                        <th class="p-3">Product</th>
                        <th class="p-3">Category</th>
                        <th class="p-3">Price</th>
                        <th class="p-3">Stock</th>
                        <th class="p-3">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-t">
                            <td class="p-3">
                                @if ($product->image_path)
                                    <img
                                        src="{{ asset('storage/' . $product->image_path) }}"
                                        alt="{{ $product->name }}"
                                        class="h-16 w-16 rounded-lg object-cover"
                                    >
                                @else
                                    <span class="text-gray-400">No image</span>
                                @endif
                            </td>

                            <td class="p-3 font-medium text-gray-900">
                                {{ $product->name }}
                            </td>

                            <td class="p-3">
                                {{ $product->category?->name ?? 'Uncategorized' }}
                            </td>

                            <td class="p-3">
                                ৳{{ number_format((float) $product->price, 2) }}
                            </td>

                            <td class="p-3">
                                <span class="{{ $product->stock < 5 ? 'font-semibold text-red-600' : 'text-gray-700' }}">
                                    {{ $product->stock }}
                                </span>
                            </td>

                            <td class="space-x-2 whitespace-nowrap p-3">
                                <a class="text-indigo-600 hover:underline"
                                   href="{{ route('owner.products.edit', $product) }}">
                                    Edit
                                </a>

                                <form class="inline"
                                      method="POST"
                                      action="{{ route('owner.products.destroy', $product) }}">
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="text-red-600 hover:underline"
                                        onclick="return confirm('Delete this product?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>

                        {{-- Inventory Adjustment --}}
                        <tr class="border-t bg-gray-50">
                            <td colspan="6" class="p-4">
                                <div class="max-w-3xl">
                                    <h3 class="font-semibold text-gray-900">
                                        Inventory Management — {{ $product->name }}
                                    </h3>

                                    <p class="mt-1 text-sm text-gray-600">
                                        Current stock: {{ $product->stock }}
                                    </p>

                                    <form
                                        method="POST"
                                        action="{{ route('owner.products.update-stock', $product) }}"
                                        class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <div>
                                            <label
                                                for="type-{{ $product->id }}"
                                                class="mb-1 block text-sm font-medium text-gray-700">
                                                Operation
                                            </label>

                                            <select
                                                id="type-{{ $product->id }}"
                                                name="type"
                                                required
                                                class="w-full rounded-md border-gray-300 text-sm">
                                                <option value="restock">Add Stock</option>
                                                <option value="adjustment">Reduce Stock</option>
                                            </select>
                                        </div>

                                        <div>
                                            <label
                                                for="quantity-{{ $product->id }}"
                                                class="mb-1 block text-sm font-medium text-gray-700">
                                                Quantity
                                            </label>

                                            <input
                                                id="quantity-{{ $product->id }}"
                                                type="number"
                                                name="quantity"
                                                min="1"
                                                max="1000000"
                                                required
                                                class="w-full rounded-md border-gray-300 text-sm"
                                                placeholder="Quantity">
                                        </div>

                                        <div>
                                            <label
                                                for="reason-{{ $product->id }}"
                                                class="mb-1 block text-sm font-medium text-gray-700">
                                                Reason
                                            </label>

                                            <input
                                                id="reason-{{ $product->id }}"
                                                type="text"
                                                name="reason"
                                                maxlength="255"
                                                required
                                                class="w-full rounded-md border-gray-300 text-sm"
                                                placeholder="Reason for change">
                                        </div>

                                        <div class="flex items-end">
                                            <button
                                                type="submit"
                                                class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                                                Update Stock
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-6 text-center text-gray-500">
                                No products found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div>
            {{ $products->links() }}
        </div>

    </div>
</x-app-layout>
