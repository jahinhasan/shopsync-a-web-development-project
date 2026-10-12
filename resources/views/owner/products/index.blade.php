
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Products</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 py-8">
        <a href="{{ route('owner.products.create') }}" class="inline-block rounded bg-indigo-600 px-4 py-2 text-white">Add Product</a>

        @if (session('success'))
            <p class="text-green-700">{{ session('success') }}</p>
        @endif

        <div class="overflow-x-auto rounded-lg border bg-white">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100"><tr><th class="p-3">Product</th><th class="p-3">Category</th><th class="p-3">Price</th><th class="p-3">Stock</th><th class="p-3">Actions</th></tr></thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-t">
                            <td class="p-3">{{ $product->name }}</td><td class="p-3">{{ $product->category->name }}</td><td class="p-3">{{ number_format((float) $product->price, 2) }}</td><td class="p-3">{{ $product->stock }}</td>
                            <td class="space-x-2 p-3"><a class="text-indigo-600" href="{{ route('owner.products.edit', $product) }}">Edit</a><form class="inline" method="POST" action="{{ route('owner.products.destroy', $product) }}">@csrf @method('DELETE')<button class="text-red-600" onclick="return confirm('Delete this product?')">Delete</button></form></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-gray-500">No products yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $products->links() }}
    </div>
</x-app-layout>
