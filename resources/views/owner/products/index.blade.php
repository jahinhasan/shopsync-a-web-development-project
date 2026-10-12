
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Products</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 py-8">
        <a href="{{ route('owner.products.create') }}" class="inline-block rounded bg-indigo-600 px-4 py-2 text-white">Add Product</a>

        @if (session('success'))
            <p class="text-green-700">{{ session('success') }}</p>
        @endif
        <form method="GET" action="{{ route('owner.products.index') }}" class="flex flex-wrap items-end gap-3 items-center rounded-lg bg-white space-x-4 p-4">
            <div>
                <label for="search" class ="mb-1 block text-sm font-medium">Search Product</label>
                <input
                id="search"
                    type="text"
                    name="search"
                    placeholder="Product name or slug"
                    value="{{ request('search') }}"
                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
            </div>
            <div>
                                <label for="search" class ="mb-1 block text-sm font-medium">
                                    Stock Status
                                </label>

                <select id="stock_status" name="stock_status" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">All products</option>
                    <option value="in_stock"  @selected(request('stock_status') === 'in_stock' )>In Stock</option>
                    <option value="out_of_stock" @selected(request('stock_status') === 'out_of_stock' )>Out of Stock</option>
                </select>
            </div>
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white">Search</button>
       <a
       href="{{ route('owner.products.index') }}"
       class="rounded-md bg-gray-600 borderpx-4 py-2"
       
       
        </form>

        <div class="overflow-x-auto rounded-lg border bg-white">
@if(session('success'))
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3">
        {{ session('success') }}
    </div>
@endif

            <table class="w-full text-left text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3">Image</th>
                        <th class="p-3">Product</th>
                        <th class="p-3">Category</th>
                        <th class="p-3">Price</th>
                        <th class="p-3">Stock</th>
                        <th class="p-3">Actions</th>
                    </tr></thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr class="border-t">
                            <td class="p-3">
                                 @if($product->image_path)
                            <img
                            src="{{ asset('storage/' . $product->image_path) }}"
                            alt="{{ $product->name }}"
                            class="h-16 w-16 object-cover rounded-lg" 
                        >
                            @endif
                            </td>
                            <td class="p-3">{{ $product->name }}</td>

                        
                            <td class="p-3">{{ $product->category->name }}</td>
                                                
                        
                        
                            <td class="p-3">{{ number_format((float) $product->price, 2) }}</td>
                            <td class="p-3">{{ $product->stock }}</td>
                            <td class="space-x-2 p-3"><a class="text-indigo-600" href="{{ route('owner.products.edit', $product) }}">Edit</a>
                                <form class="inline" method="POST" action="{{ route('owner.products.destroy', $product) }}">
                                    @csrf @method('DELETE')<button class="text-red-600" onclick="return confirm('Delete this product?')">Delete</button>
                                </form></td>
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
