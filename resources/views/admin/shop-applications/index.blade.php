
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-medium text-indigo-600">ShopSync Admin</p>
                <h1 class="mt-1 text-2xl font-bold text-gray-900">
                    Shop Application Review
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Review applications assigned to your account.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl space-y-6">

            @if (session('success'))
                <div role="status" class="rounded-xl bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ([
                    'pending' => 'Pending',
                    'needs_information' => 'Needs information',
                    'approved' => 'Approved',
                    'rejected' => 'Rejected',
                ] as $key => $label)
                    <a
                        href="{{ route('admin.shop-applications.index', ['status' => $key]) }}"
                        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300"
                    >
                        <p class="text-sm text-gray-500">{{ $label }}</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">
                            {{ $statusCounts->get($key, 0) }}
                        </p>
                    </a>
                @endforeach
            </div>

            <form
                method="GET"
                action="{{ route('admin.shop-applications.index') }}"
                class="flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 sm:flex-row"
            >
                <input
                    type="search"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="Search shop, applicant or email"
                    class="min-w-0 flex-1 rounded-xl border-gray-300"
                >

                <select name="status" class="rounded-xl border-gray-300 sm:w-56">
                    <option value="">All statuses</option>
                    @foreach ([
                        'pending' => 'Pending',
                        'needs_information' => 'Needs information',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ] as $key => $label)
                        <option
                            value="{{ $key }}"
                            @selected(($filters['status'] ?? '') === $key)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>

                <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                    Filter
                </button>

                <a
                    href="{{ route('admin.shop-applications.index') }}"
                    class="rounded-xl border border-gray-200 px-5 py-2.5 text-center text-sm font-semibold text-gray-700"
                >
                    Reset
                </a>
            </form>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr class="text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="px-5 py-4">Shop / Applicant</th>
                                <th class="px-5 py-4">Business type</th>
                                <th class="px-5 py-4">Submitted</th>
                                <th class="px-5 py-4">Status</th>
                                <th class="px-5 py-4 text-right">Action</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @forelse ($applications as $application)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-4">
                                        <p class="font-semibold text-gray-900">
                                            {{ $application->shop_name }}
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500">
                                            {{ $application->applicant?->name }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ $application->applicant?->email }}
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-700">
                                        {{ $application->shop_type }}
                                    </td>

                                    <td class="px-5 py-4 text-sm text-gray-600">
                                        {{ $application->created_at->format('d M Y, h:i A') }}
                                    </td>

                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                            {{ $application->status === 'approved'
                                                ? 'bg-green-50 text-green-700'
                                                : ($application->status === 'rejected'
                                                    ? 'bg-red-50 text-red-700'
                                                    : ($application->status === 'needs_information'
                                                        ? 'bg-orange-50 text-orange-700'
                                                        : 'bg-amber-50 text-amber-700')) }}">
                                            {{ str($application->status)->replace('_', ' ')->title() }}
                                        </span>
                                    </td>

                                    <td class="px-5 py-4 text-right">
                                        <a
                                            href="{{ route('admin.shop-applications.show', $application->id) }}"
                                            class="inline-flex rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700"
                                        >
                                            Review
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-14 text-center">
                                        <p class="font-semibold text-gray-900">
                                            No applications found
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500">
                                            Assigned applications will appear here.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-gray-100 p-4">
                    {{ $applications->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
