
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">My Orders</h2>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-4 px-4 py-8">
        @if (session('success'))
            <div class="rounded bg-green-100 p-4 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @forelse ($orders as $order)
            <div class="rounded-lg border bg-white p-5">
                <div class="flex flex-wrap justify-between gap-3">
                    <div>
                        <p class="font-semibold">
                            {{ $order->order_number }}
                        </p>

                        <p class="mt-1 text-sm text-gray-500">
                            {{ $order->created_at->format('d M Y, h:i A') }}
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="font-bold">
                            ৳{{ number_format((float) $order->total, 2) }}
                        </p>

                        <p class="mt-1 text-sm text-gray-600">
                            Item statuses:
                            <span class="font-medium">
                                {{ $order->items->groupBy('status')->map(fn ($items, $status) => ucfirst($status) . ': ' . $items->count())->implode(' · ') }}
                            </span>
                        </p>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('orders.show', $order) }}"
                       class="text-indigo-600 hover:underline">
                        View Order Details
                    </a>
                </div>
            </div>
        @empty
            <div class="rounded-lg border bg-white p-6 text-gray-600">
                You have not placed any orders yet.
                <a href="{{ route('shop.index') }}"
                   class="ml-1 text-indigo-600 hover:underline">
                    Start Shopping
                </a>
            </div>
        @endforelse

        {{ $orders->links() }}
    </div>
</x-app-layout>
