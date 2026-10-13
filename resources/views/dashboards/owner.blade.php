<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Business Owner Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">Manage your store, inventory and orders.</p>
            </div>

<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ([
        ['Products', $totalProducts],
        ['Available Stock', $totalStock],
        ['Orders', $totalOrders],
        ['Sales', '৳' . number_format((float) $totalSales, 2)],
    ] as [$title, $value])
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm text-gray-500">{{ $title }}</p>
            <h3 class="mt-2 text-2xl font-bold text-gray-900">{{ $value }}</h3>
        </div>
    @endforeach
</div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Store Management</h3>
                <p class="mt-2 text-sm text-gray-600">Product management, stock updates, order processing and sales reports will be connected to the database in upcoming modules.</p>
            </div>
        </div>
    </div>
</x-app-layout>
