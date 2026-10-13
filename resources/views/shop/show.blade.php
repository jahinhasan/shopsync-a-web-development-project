<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Product Details</h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-4 px-4 py-8">
        <a href="{{ route('shop.index') }}" class="text-indigo-600 hover:underline">&larr;Back to Shop</a>
        <div class="mt-6 grid gap-8 rounded-lg border bg-white p-6 md:grid-cols-2">
            <div>
                @if ($product->image_path)
                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}"
                        class="h-64 w-full object-cover rounded-lg">
                @endif()
            </div>

            <div>
                <h1 class="text-2x1 font-bold">
                    {{ $product->name }}



                </h1>


                <p class="mt-2 text-gray-500">
                    {{ $product->category?->name ?? 'Uncategorized' }}
                </p>

                <p class="mt-4 text-2xl font-semibold">
                    ৳{{ number_format((float) $product->price, 2) }}
                </p>

                <p class="mt-4">
                    {{ $product->description ?: 'No description available.' }}
                </p>

                <p class="mt-4 text-green-700">
                    In stock: {{ $product->stock }}
                </p>

                <form method="POST" action="{{ route('cart.add', $product) }}" class="mt-6">
                    @csrf

                    <button type="submit" class="rounded-md bg-indigo-600 px-5 py-2 text-white hover:bg-indigo-700">
                        Add to Cart
                    </button>
                </form>
            </div>


        </div>

    </div>



</x-app-layout>
