<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Order Management
        </h2>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-4 px-4 py-8">

        {{-- Success Message --}}
        @if (session('success'))
            <div class="rounded-lg bg-green-100 p-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        {{-- Validation Errors --}}
        @if ($errors->any())
            <div class="rounded-lg bg-red-100 p-3 text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Orders List --}}
        @forelse ($orders as $order)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">

                <div class="flex flex-wrap justify-between gap-4">
                    <div>
                        <p class="font-semibold text-gray-900">
                            {{ $order->order_number }}
                        </p>

                        <p class="mt-1 text-sm text-gray-600">
                            Customer: {{ $order->customer_name }}
                        </p>

                        <p class="text-sm text-gray-600">
                            Phone: {{ $order->phone }}
                        </p>

                        <p class="mt-1 text-sm text-gray-600">
                            Shipping Address: {{ $order->shipping_address }}
                        </p>
                    </div>

                    <div>
                        <p class="text-lg font-bold text-gray-900">
                            ৳{{ number_format((float) $order->items->sum('subtotal'), 2) }}
                        </p>

                        <p class="mt-1 text-sm text-gray-600">
                            Current Status:
                            <span class="font-medium capitalize">
                                {{ $order->status }}
                            </span>
                        </p>
                    </div>
                    {{-- Ordered Products --}}
                    <div class="mt-5 border-t border-gray-200 pt-4">
                        <h4 class="mb-3 font-semibold text-gray-900">
                            Ordered Products
                        </h4>

                        <div class="space-y-3">
                            @foreach ($order->items as $item)
                                <div class="flex flex-wrap justify-between gap-4 rounded-lg bg-gray-50 p-4">
                                    <div>
                                        <p class="font-medium text-gray-800">
                                            {{ $item->product_name }}
                                        </p>

                                        <p class="text-sm text-gray-500">
                                            Quantity: {{ $item->quantity }}
                                        </p>

                                        <p class="mt-1 text-sm text-gray-600">
                                            Status:
                                            <span class="font-medium capitalize">
                                                {{ $item->status }}
                                            </span>
                                        </p>
                                    </div>

                                    <div class="text-right">
                                        <p class="text-sm text-gray-600">
                                            Unit Price:
                                            ৳{{ number_format((float) $item->unit_price, 2) }}
                                        </p>

                                        <p class="font-semibold text-gray-900">
                                            Subtotal:
                                            ৳{{ number_format((float) $item->subtotal, 2) }}
                                        </p>
                                    </div>

                                    <form method="POST"
                                        action="{{ route('owner.orders.update-status', $order) }}"
                                        class="flex w-full flex-wrap items-center gap-3 border-t border-gray-200 pt-3">
                                        @csrf
                                        @method('PATCH')

                                        <input type="hidden" name="item_id" value="{{ $item->id }}">

                                        <label for="status-{{ $item->id }}"
                                            class="text-sm font-medium text-gray-700">
                                            Update product status
                                        </label>

                                        <select id="status-{{ $item->id }}" name="status" required
                                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            @foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
                                                <option value="{{ $status }}" @selected($item->status === $status)>
                                                    {{ ucfirst($status) }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <button type="submit"
                                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-indigo-700">
                                            Update Status
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>



            </div>
        @empty
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500">
                No orders found for your products.
            </div>
        @endforelse

        {{-- Pagination --}}
        <div class="pt-2">
            {{ $orders->links() }}
        </div>

        {{-- Back to Dashboard --}}
        <div class="pt-2">
            <a href="{{ route('owner.dashboard') }}" class="text-sm font-medium text-indigo-600 hover:underline">
                &larr; Back to Dashboard
            </a>
        </div>

    </div>
</x-app-layout>
