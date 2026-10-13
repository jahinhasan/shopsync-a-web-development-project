<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Shopping Cart</h2>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-4 px-4 py-8">
        @if (session('success'))
            <p class="text-green-700">{{ session('success') }}</p>
        @endif

        @if (session('error'))
            <p class="text-red-600">{{ session('error') }}</p>
        @endif

        @php
            $total = 0;
        @endphp

        @forelse ($cart as $id => $quantity)
            @if ($product = $products->get($id))
                @php
                    $subtotal = (float) $product->price * $quantity;
                    $total += $subtotal;
                @endphp

                <div class="flex flex-wrap items-center justify-between gap-4 rounded-lg border bg-white p-4">
                    <div>
                        <h3 class="font-semibold">{{ $product->name }}</h3>
                        <p>Price: ৳{{ number_format((float) $product->price, 2) }}</p>
                        <form method="POST" action="{{ route('cart.update', $product) }}"
                            class="mt-2 flex items-center gap-2">
                            @csrf
                            @method('PATCH')

                            <label for="quantity-{{ $product->id }}">Quantity:</label>

                            <input id="quantity-{{ $product->id }}" type="number" name="quantity"
                                value="{{ $quantity }}" min="1" max="{{ $product->stock }}" required
                                class="w-20 rounded-md border-gray-300">

                            <button type="submit" class="text-indigo-600 hover:underline">
                                Update
                            </button>
                        </form>
                        <p>Subtotal: ৳{{ number_format($subtotal, 2) }}</p>
                    </div>

                    <form method="POST" action="{{ route('cart.remove', $product) }}">
                        @csrf
                        @method('DELETE')

                        <button type="submit" class="text-red-600 hover:underline">
                            Remove
                        </button>
                    </form>
                </div>
            @endif
        @empty
            <p class="text-gray-500">Your cart is empty.</p>
        @endforelse

        @if (count($cart) > 0)
            <div class="text-right text-xl font-bold">
                Total: ৳{{ number_format($total, 2) }}
            </div>
        @endif

        <a href="{{ route('shop.index') }}" class="inline-block text-indigo-600 hover:underline">
            Continue Shopping
        </a>
    </div>
</x-app-layout>
