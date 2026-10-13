<x-app-layout>

    <x-slot name="header">
        <h2 class="text-xl font-semibold">Shop</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8">
        <div class="grid grid-cols-1 gap-6 am:grid-cols-2 lg:grid-cols-3">
            @forelse ($products as $product)
                <article class="overflow-hidden rounded-lg border bg-white p-4 shadow transition hover:shadow-lg">
                    @if ($product->image_path)
                        <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}"
                            class="mb-4h-16 w-full Rounded object-cover">
                    @endif
                    <h3 class="text-lg font-semibold"><a href="{{ route('shop.show',$product) }}" class="hover:text-indigo-600"
                        >
                        {{ $product->name }}
                    </a>
                </h3>
                    <p class="text-sm text-gray-500">{{ $product->category?->name ?? 'uncategorized' }}</p>

                    <p class="mt-2 font-medium">Taka: {{ number_format((float) $product->price, 2) }}</p>
                    <p class="mt-1 text-sm text-gray-700">In Stock: {{ $product->stock }}</p>


                </article>
            @empty
                <p class="text-gray-500">No products available.</p>
            @endforelse



        </div>


        <div>
            {{ $products->links() }}

        </div>

    </div>


</x-app-layout>
