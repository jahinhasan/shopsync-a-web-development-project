<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Order Details</h2>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8">
        <div class="rounded-lg border bg-white p-5">
            <h3 class="font-semibold">
                Order: {{ $order->order_number }}
            </h3>

            <p class="mt-2">
                Status: <span class="capitalize">{{ $order->status }}</span>
            </p>

            <p class="mt-1">
                Payment: {{ strtoupper($order->payment_method) }}
                ({{ $order->payment_status }})
            </p>

            <p class="mt-1">
                Date: {{ $order->created_at->format('d M Y, h:i A') }}
            </p>
        </div>

        <div class="rounded-lg border bg-white p-5">
            <h3 class="mb-4 font-semibold">Ordered Products</h3>

            @foreach ($order->items as $item)
                <div class="flex justify-between gap-4 border-b py-3">
                    <div>
                        <p>{{ $item->product_name }}</p>
                        <p class="mt-1 text-sm text-gray-600">
                            Fulfillment Status:
                            <span class="font-medium capitalize">{{ $item->status }}</span>
                        </p>
                        <p class="text-sm text-gray-500">
                            ৳{{ number_format((float) $item->unit_price, 2) }}
                            × {{ $item->quantity }}
                        </p>
                    </div>

                    <p class="font-medium">
                        ৳{{ number_format((float) $item->subtotal, 2) }}
                    </p>
                </div>
            @endforeach

            <p class="mt-4 text-right text-lg font-bold">
                Total: ৳{{ number_format((float) $order->total, 2) }}
            </p>
        </div>

        <div class="rounded-lg border bg-white p-5">
            <h3 class="mb-2 font-semibold">Shipping Information</h3>
            <p>{{ $order->customer_name }}</p>
            <p>{{ $order->phone }}</p>
            <p class="whitespace-pre-line">{{ $order->shipping_address }}</p>
        </div>


        <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-5">
            <h3 class="font-bold text-gray-900">Payment information</h3>

            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <div>
                    <p class="text-xs text-gray-500">Payment method</p>
                    <p class="mt-1 font-semibold text-gray-900">
                        {{ strtoupper($order->payment_method) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs text-gray-500">Overall payment status</p>
                    <p
                        class="mt-1 font-semibold
                {{ $order->payment_status === 'paid' ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ ucfirst($order->payment_status) }}
                    </p>
                </div>
            </div>

            @if ($order->voucher_code)
                <div class="mt-4 border-t border-gray-100 pt-4">
                    <p class="text-xs text-gray-500">Voucher used</p>
                    <p class="mt-1 font-mono font-semibold text-indigo-700">
                        {{ $order->voucher_code }}
                    </p>
                    <p class="mt-1 text-sm text-emerald-700">
                        ShopSync promotional discount:
                        ৳{{ number_format((float) $order->voucher_discount_total, 2) }}
                    </p>
                </div>
            @endif
        </div>
        <a href="{{ route('orders.index') }}" class="text-indigo-600 hover:underline">
            Back to My Orders
        </a>
    </div>
</x-app-layout>
