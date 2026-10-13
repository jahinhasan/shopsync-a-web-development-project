```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">
            Inventory History
        </h2>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8">

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">
                    Stock Transactions
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Review your recorded inventory changes.
                </p>
            </div>

            <a href="{{ route('owner.products.index') }}"
               class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                Back to Products
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-lg bg-green-100 p-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="p-4">Date</th>
                        <th class="p-4">Product</th>
                        <th class="p-4">Type</th>
                        <th class="p-4">Before</th>
                        <th class="p-4">Change</th>
                        <th class="p-4">After</th>
                        <th class="p-4">Reason</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">
                    @forelse ($transactions as $transaction)
                        <tr>
                            <td class="whitespace-nowrap p-4">
                                {{ $transaction->created_at->format('d M Y, h:i A') }}
                            </td>

                            <td class="p-4 font-medium text-gray-900">
                                {{ $transaction->product?->name ?? 'Deleted product' }}
                            </td>

                            <td class="p-4">
                                {{ ucfirst($transaction->type) }}
                            </td>

                            <td class="p-4">
                                {{ $transaction->quantity_before }}
                            </td>

                            <td class="p-4 font-semibold {{ $transaction->quantity_change > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $transaction->quantity_change > 0 ? '+' : '' }}{{ $transaction->quantity_change }}
                            </td>

                            <td class="p-4">
                                {{ $transaction->quantity_after }}
                            </td>

                            <td class="p-4">
                                {{ $transaction->reason ?? '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-6 text-center text-gray-500">
                                No inventory history found. Stock adjustments will appear here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $transactions->links() }}
        </div>

    </div>
</x-app-layout>
```