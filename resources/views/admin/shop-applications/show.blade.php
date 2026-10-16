
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('admin.shop-applications.index') }}"
                   class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    ← All applications
                </a>
                <h1 class="mt-2 text-2xl font-bold text-gray-900">
                    {{ $application->shop_name }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Submitted by {{ $application->applicant?->name }}
                    · {{ $application->applicant?->email }}
                </p>
            </div>

            <span class="inline-flex rounded-full bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700">
                {{ str($application->status)->replace('_', ' ')->title() }}
            </span>
        </div>
    </x-slot>

    <div class="min-h-screen bg-gray-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-5xl space-y-6">

            @if (session('success'))
                <div class="rounded-xl bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-xl bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold">Please correct the following:</p>
                    <ul class="mt-2 list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Business information --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-lg font-bold text-gray-900">Business information</h2>

                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    @foreach ([
                        'Shop name' => $application->shop_name,
                        'Shop type' => $application->other_shop_type
                            ? $application->shop_type . ' — ' . $application->other_shop_type
                            : $application->shop_type,
                        'Business stage' => $application->business_stage,
                        'Estimated products' => $application->estimated_product_count,
                        'Business email' => $application->business_email,
                        'Business phone' => $application->business_phone,
                        'Country' => $application->country,
                        'Division' => $application->division,
                        'District' => $application->district,
                        'Upazila' => $application->upazila,
                        'Postal code' => $application->postal_code,
                        'Full address' => $application->full_address,
                        'Product categories' => $application->product_categories,
                        'Business history' => $application->business_history,
                        'Previous platforms' => $application->previous_platforms,
                        'Supplier information' => $application->supplier_information,
                        'Additional information' => $application->additional_information,
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {{ $label }}
                            </dt>
                            <dd class="mt-1 whitespace-pre-line break-words text-sm text-gray-900">
                                {{ filled($value) ? $value : 'Not provided' }}
                            </dd>
                        </div>
                    @endforeach

                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Description
                        </dt>
                        <dd class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-800">
                            {{ $application->description }}
                        </dd>
                    </div>
                </dl>
            </section>

            {{-- Identity information --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-lg font-bold text-gray-900">Identity verification</h2>

                <dl class="mt-5 grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Legal name
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $application->legal_name }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Date of birth
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $application->date_of_birth?->format('d M Y') ?? 'Not provided' }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Document type
                        </dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ str($application->identity_document_type)->replace('_', ' ')->title() }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Document number
                        </dt>
                        <dd class="mt-1 break-all text-sm text-gray-900">
                            {{ $application->identity_document_number ?: 'Not provided' }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 grid gap-6 sm:grid-cols-2">
                    <div>
                        <p class="mb-2 text-sm font-semibold text-gray-700">
                            Identity camera photo
                        </p>
                        @if ($application->identity_photo_path)
                            <a
                                href="{{ route('admin.shop-applications.documents', [
                                    'application' => $application->id,
                                    'document' => 'identity-photo',
                                ]) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-gray-50"
                            >
                                Open private photo
                            </a>
                        @else
                            <p class="text-sm text-gray-500">No photo uploaded.</p>
                        @endif
                    </div>

                    <div>
                        <p class="mb-2 text-sm font-semibold text-gray-700">
                            Identity document
                        </p>
                        @if ($application->identity_document_path)
                            <a
                                href="{{ route('admin.shop-applications.documents', [
                                    'application' => $application->id,
                                    'document' => 'identity-document',
                                ]) }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-gray-50"
                            >
                                Open private document
                            </a>
                        @else
                            <p class="text-sm text-gray-500">No document uploaded.</p>
                        @endif
                    </div>
                </div>
            </section>

            {{-- Business evidence --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-lg font-bold text-gray-900">Uploaded business evidence</h2>

                @if ($application->media->isEmpty())
                    <p class="mt-4 text-sm text-gray-500">
                        No additional evidence uploaded.
                    </p>
                @else
                    <div class="mt-5 divide-y divide-gray-100">
                        @foreach ($application->media as $media)
                            <div class="flex flex-wrap items-center justify-between gap-3 py-4">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ $media->original_name ?: 'Evidence file' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ str($media->category)->replace('_', ' ')->title() }}
                                        · {{ $media->mime_type ?: 'Unknown type' }}
                                        @if ($media->file_size)
                                            · {{ number_format($media->file_size / 1024, 1) }} KB
                                        @endif
                                    </p>
                                </div>

                                <a
                                    href="{{ route('admin.shop-applications.media', [
                                        'application' => $application->id,
                                        'mediaId' => $media->id,
                                    ]) }}"
                                    class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-gray-50"
                                >
                                    Download privately
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            {{-- Review history --}}
            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-lg font-bold text-gray-900">Review history</h2>

                <div class="mt-5 space-y-4">
                    @forelse ($application->reviews as $review)
                        <article class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-semibold text-gray-900">
                                    {{ str($review->action)->replace('_', ' ')->title() }}
                                </p>
                                <time class="text-xs text-gray-500">
                                    {{ $review->created_at->format('d M Y, h:i A') }}
                                </time>
                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                {{ $review->actor?->name ?? 'System' }}
                                · {{ ucfirst($review->actor_role) }}
                            </p>

                            @if ($review->comment)
                                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-gray-700">
                                    {{ $review->comment }}
                                </p>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-gray-500">
                            No previous review actions recorded.
                        </p>
                    @endforelse
                </div>
            </section>

            {{-- Decision controls --}}
            @if (in_array($application->status, ['pending', 'needs_information'], true))
                <section class="rounded-2xl border border-indigo-100 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="text-lg font-bold text-gray-900">Make a decision</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Choose one action after reviewing the submitted information.
                    </p>

                    <div class="mt-6 grid gap-6 lg:grid-cols-2">

                        {{-- Request additional information --}}
                        <form
                            method="POST"
                            action="{{ route('admin.shop-applications.request-information', $application->id) }}"
                            class="space-y-3 rounded-xl border border-amber-200 bg-amber-50/50 p-5"
                        >
                            @csrf
                            @method('PATCH')

                            <h3 class="font-semibold text-gray-900">
                                Request more information
                            </h3>

                            <label for="request-comment" class="block text-sm text-gray-600">
                                Explain exactly what the applicant needs to provide.
                            </label>

                            <textarea
                                id="request-comment"
                                name="comment"
                                required
                                minlength="5"
                                maxlength="3000"
                                rows="4"
                                class="w-full rounded-xl border-gray-300"
                                placeholder="Please provide..."
                            >{{ old('comment') }}</textarea>

                            <button
                                class="w-full rounded-xl bg-amber-600 px-4 py-3 text-sm font-semibold text-white hover:bg-amber-700"
                            >
                                Request information
                            </button>
                        </form>

                        {{-- Approve --}}
                        <form
                            method="POST"
                            action="{{ route('admin.shop-applications.approve', $application->id) }}"
                            class="space-y-3 rounded-xl border border-green-200 bg-green-50/50 p-5"
                        >
                            @csrf
                            @method('PATCH')

                            <h3 class="font-semibold text-gray-900">Approve shop</h3>

                            <label for="approval-comment" class="block text-sm text-gray-600">
                                Optional approval note
                            </label>

                            <textarea
                                id="approval-comment"
                                name="comment"
                                maxlength="3000"
                                rows="4"
                                class="w-full rounded-xl border-gray-300"
                                placeholder="Optional note for the review history"
                            >{{ old('comment') }}</textarea>

                            <button
                                class="w-full rounded-xl bg-green-700 px-4 py-3 text-sm font-semibold text-white hover:bg-green-800"
                            >
                                Approve application
                            </button>
                        </form>

                        {{-- Reject --}}
                        <form
                            method="POST"
                            action="{{ route('admin.shop-applications.reject', $application->id) }}"
                            class="space-y-3 rounded-xl border border-red-200 bg-red-50/50 p-5 lg:col-span-2"
                            onsubmit="return confirm('Are you sure you want to reject this application?');"
                        >
                            @csrf
                            @method('PATCH')

                            <h3 class="font-semibold text-gray-900">Reject application</h3>

                            <label for="rejection-reason" class="block text-sm text-gray-600">
                                Rejection reason visible to the applicant
                            </label>

                            <textarea
                                id="rejection-reason"
                                name="rejection_reason"
                                required
                                minlength="5"
                                maxlength="3000"
                                rows="3"
                                class="w-full rounded-xl border-gray-300"
                                placeholder="Explain the reason for rejection..."
                            >{{ old('rejection_reason') }}</textarea>

                            <button
                                class="rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-700"
                            >
                                Reject application
                            </button>
                        </form>
                    </div>
                </section>
            @else
                <section class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h2 class="font-bold text-gray-900">Application decision recorded</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        This application is currently
                        <strong>{{ str($application->status)->replace('_', ' ') }}</strong>.
                        The decision controls are closed.
                    </p>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
