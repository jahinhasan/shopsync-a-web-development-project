
<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-indigo-600">
                ShopSync Seller Portal
            </p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">
                Shop Application
            </h1>
        </div>
    </x-slot>

    <div class="min-h-[75vh] bg-gray-50 px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl">

            @if (session('success'))
                <div class="mb-6 rounded-xl bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('warning'))
                <div class="mb-6 rounded-xl bg-amber-50 p-4 text-sm text-amber-800">
                    {{ session('warning') }}
                </div>
            @endif

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <p class="text-sm font-semibold uppercase tracking-wider text-gray-500">
                    Application status
                </p>

                <h2 class="mt-3 text-2xl font-bold text-gray-900">
                    {{ $application->shop_name }}
                </h2>

                <p class="mt-3 text-gray-600">
                    Your shop application has been submitted for review.
                    You will receive access to seller features after admin approval.
                </p>

                <div class="mt-6 border-t border-gray-100 pt-6">
                    <p class="text-sm text-gray-500">Current status</p>

                    <span class="mt-2 inline-flex rounded-full px-4 py-2 text-sm font-semibold
                        {{ $application->status === 'approved'
                            ? 'bg-green-50 text-green-700'
                            : ($application->status === 'rejected'
                                ? 'bg-red-50 text-red-700'
                                : 'bg-amber-50 text-amber-700') }}">
                        {{ ucfirst($application->status) }}
                    </span>
                </div>

                @if ($application->rejection_reason)
                    <div class="mt-6 rounded-xl bg-red-50 p-4 text-sm text-red-800">
                        <strong>Admin feedback:</strong>
                        {{ $application->rejection_reason }}
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
