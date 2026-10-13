 <x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Checkout</h2>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8">
        @if ($errors->any())
            <div class="rounded border border-red-300 bg-red-50 p-4 text-red-700">
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="rounded-lg border bg-white p-5">
            <h3 class="mb-3 font-semibold">Order Summary</h3>

            @php $total = 0; @endphp

            @foreach ($cart as $id => $quantity)
                @if ($product = $products->get($id))
                    @php $subtotal = (float) $product->price * $quantity; @endphp

                    <div class="flex justify-between gap-4 border-b py-2">
                        <span>{{ $product->name }} × {{ $quantity }}</span>
                        <span>৳{{ number_format($subtotal, 2) }}</span>
                    </div>

                    @php $total += $subtotal; @endphp
                @endif
            @endforeach

            <p class="mt-4 text-right text-lg font-bold">
                Total: ৳{{ number_format($total, 2) }}
            </p>

            <p class="mt-2 text-sm text-gray-500">
                Payment method: Cash on Delivery
            </p>
        </div>

        <form method="POST" action="{{ route('checkout.place') }}"
              class="space-y-4 rounded-lg border bg-white p-5">
            @csrf

            <div>
                <label for="customer_name" class="mb-1 block font-medium">
                    Full name
                </label>
                <input id="customer_name" name="customer_name"
                       value="{{ old('customer_name') }}" required
                       maxlength="255"
                       class="w-full rounded-md border-gray-300">
            </div>

            <div>
                <label for="phone" class="mb-1 block font-medium">
                    Phone number
                </label>
                <input id="phone" name="phone" type="tel"
                       value="{{ old('phone') }}" required maxlength="30"
                       class="w-full rounded-md border-gray-300">
            </div>

            <div>
                <label for="shipping_address" class="mb-1 block font-medium">
                    Shipping address
                </label>
                <textarea id="shipping_address" name="shipping_address"
                          required maxlength="2000" rows="4"
                          class="w-full rounded-md border-gray-300">{{ old('shipping_address') }}</textarea>
            </div>

            <button type="submit"
                    class="rounded-md bg-indigo-600 px-5 py-2 text-white hover:bg-indigo-700">
                Place Order (COD)
            </button>
        </form>
    </div>
</x-app-layout>