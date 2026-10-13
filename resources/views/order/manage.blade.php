
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold">Order Management</h2>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-4 px-4 py-8">
        @if (session('success'))
            <div class="rounded bg-green-100 p-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded bg-red-100 p-3 text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        @forelse ($orders as $order)
            <div class="rounded-lg border bg-white p-5">
                <div class="flex flex-wrap justify-between gap-3">
                    <div>
                        <p class="font-semibold">
                            {{ $order->order_number }}
                        </p>
                        <p class="text-sm text-gray-600">
                            Customer: {{ $order->customer_name }}
                        </p>
                        <p class="text-sm text-gray-600">
                            {{ $order->phone }}
                        </p>
                    </div>

                    <div>
                        <p class="font-bold">
                            ৳{{ number_format((float) $order->total, 2) }}
                        </p>
                        <p class="text-sm capitalize">
                            Current status: {{ $order->status }}
                        </p>
                    </div>
                </div>

                <form method="POST"
                      action="{{ route('orders.update-status', $order) }}"
                      class="mt-4 flex flex-wrap items-center gap-3">
                    @csrf
                    @method('PATCH')

                    <label for="status-{{ $order->id }}">Update status</label>

                    <select id="status-{{ $order->id }}"
                            name="status"
                            class="rounded-md border-gray-300">
                        @foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
                            <option value="{{ $status }}"
                                @selected($order->status === $status)>
                                {{ ucfirst($status) }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-white">
                        Update
                    </button>
                </form>
            </div>
        @empty
            <p class="rounded-lg border bg-white p-5">
                No orders found.
            </p>
        @endforelse

        {{ $orders->links() }}
    </div>
</x-app-layout>
