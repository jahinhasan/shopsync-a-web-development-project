
<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium text-indigo-600">
                ShopSync Seller Portal
            </p>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">
                Shop Application Status
            </h1>
        </div>
    </x-slot>

    @php
        $adminReviews = $application->reviews
            ->where('actor_role', 'admin')
            ->whereNotNull('comment');
    @endphp

    <div class="min-h-[75vh] bg-gray-50 px-4 py-10 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl space-y-6">

            {{-- Success notification --}}
            @if (session('success'))
                <div role="status"
                     class="rounded-xl bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            {{-- Warning notification --}}
            @if (session('warning'))
                <div role="status"
                     class="rounded-xl bg-amber-50 p-4 text-sm text-amber-800">
                    {{ session('warning') }}
                </div>
            @endif

            {{-- Application status --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wider text-gray-500">
                            Your shop
                        </p>

                        <h2 class="mt-3 text-2xl font-bold text-gray-900">
                            {{ $application->shop_name }}
                        </h2>

                        <p class="mt-2 text-sm text-gray-500">
                            Application submitted
                            {{ $application->created_at->format('d M Y') }}
                        </p>
                    </div>

                    <span class="inline-flex rounded-full px-4 py-2 text-sm font-semibold
                        {{ $application->status === 'approved'
                            ? 'bg-green-50 text-green-700'
                            : ($application->status === 'rejected'
                                ? 'bg-red-50 text-red-700'
                                : ($application->status === 'needs_information'
                                    ? 'bg-orange-50 text-orange-700'
                                    : 'bg-amber-50 text-amber-700')) }}">
                        {{ str($application->status)->replace('_', ' ')->title() }}
                    </span>
                </div>

                <div class="mt-6 border-t border-gray-100 pt-6">
                    @if ($application->status === 'pending')
                        <h3 class="font-semibold text-gray-900">
                            Your application is under review
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-gray-600">
                            Your application has been submitted successfully.
                            Please wait while the admin reviews your information.
                            Seller features will remain locked until approval.
                        </p>

                    @elseif ($application->status === 'needs_information')
                        <h3 class="font-semibold text-orange-800">
                            Additional information required
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-gray-600">
                            The admin needs some additional information or changes.
                            Read the feedback below, update your existing application,
                            and submit it again.
                        </p>

                    @elseif ($application->status === 'rejected')
                        <h3 class="font-semibold text-red-800">
                            Your application was rejected
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-gray-600">
                            Please read the admin's decision and rejection reason below.
                        </p>

                    @elseif ($application->status === 'approved')
                        <h3 class="font-semibold text-green-800">
                            Your shop has been approved!
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-gray-600">
                            Your seller application has been approved.
                        </p>

                        <a href="{{ route('owner.dashboard') }}"
                           class="mt-4 inline-flex rounded-xl bg-green-700 px-5 py-3 text-sm font-semibold text-white hover:bg-green-800">
                            Go to seller dashboard
                        </a>
                    @endif
                </div>
            </section>

            {{-- Admin feedback and review history --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">
                        Admin feedback
                    </h2>

                    <p class="mt-1 text-sm text-gray-500">
                        Comments and decisions related to your application.
                    </p>
                </div>

                <div class="mt-5 space-y-4">
                    @forelse ($adminReviews as $review)
                        <article class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-gray-900">
                                        {{ str($review->action)->replace('_', ' ')->title() }}
                                    </p>

                                    <p class="mt-1 text-xs text-gray-500">
                                        From the review team
                                    </p>
                                </div>

                                <time class="text-xs text-gray-500">
                                    {{ $review->created_at->format('d M Y, h:i A') }}
                                </time>
                            </div>

                            <p class="mt-4 whitespace-pre-line text-sm leading-7 text-gray-700">
                                {{ $review->comment }}
                            </p>
                        </article>
                    @empty
                        <div class="rounded-xl border border-dashed border-gray-300 p-6 text-center">
                            <p class="font-medium text-gray-700">
                                No admin comments yet
                            </p>

                            <p class="mt-2 text-sm text-gray-500">
                                Any feedback or request from the admin will appear here.
                            </p>
                        </div>
                    @endforelse
                </div>

                {{-- Rejection reason --}}
                @if ($application->status === 'rejected' && $application->rejection_reason)
                    <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-5">
                        <h3 class="font-semibold text-red-800">
                            Rejection reason
                        </h3>

                        <p class="mt-2 whitespace-pre-line text-sm leading-7 text-red-700">
                            {{ $application->rejection_reason }}
                        </p>
                    </div>
                @endif

                {{-- Edit and resubmit --}}
                @if ($application->status === 'needs_information')
                    <div class="mt-6 rounded-xl border border-orange-200 bg-orange-50 p-5">
                        <h3 class="font-semibold text-orange-900">
                            Update your application
                        </h3>

                        <p class="mt-2 text-sm leading-6 text-orange-800">
                            Your previously submitted information will be shown
                            in the form. Update the required fields and resubmit
                            your application for another review.
                        </p>

                        <a href="{{ route('owner.shop-application.create') }}"
                           class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700 sm:w-auto">
                            Update application and resubmit
                        </a>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
