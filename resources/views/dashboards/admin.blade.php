
<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-gray-800">Admin Dashboard</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}</h1>
                <p class="mt-1 text-sm text-gray-500">Manage your shopping platform from one place.</p>
            </div>

            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Users', 'Manage customer accounts'],
                    ['Stores', 'Review business owners'],
                    ['Products', 'Manage product listings'],
                    ['Orders', 'Monitor all orders'],
                ] as [$title, $description])
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="font-semibold text-gray-900">{{ $title }}</h3>
                        <p class="mt-2 text-sm text-gray-500">{{ $description }}</p>
                    </div>
                @endforeach
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900">Platform Management</h3>
                <p class="mt-2 text-sm text-gray-600">User management, store approvals, product moderation, payments and reports will be implemented in the upcoming modules.</p>
            </div>
        </div>
    </div>
</x-app-layout>
