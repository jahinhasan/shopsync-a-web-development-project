@php
    $lockUser = auth()->user();

    $shopLockApplication = $lockUser
        && $lockUser->role === 'business_owner'
            ? $lockUser->shopApplication
            : null;

    $showShopApplicationLock = $shopLockApplication
        && !in_array(
            $shopLockApplication->status,
            ['approved', 'needs_information'],
            true
        );
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @if ($showShopApplicationLock)
        <meta http-equiv="refresh" content="15">
    @endif
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@if ($showShopApplicationLock)
    {{-- Blurred page overlay: blocks interaction with everything behind it --}}
    <div
        class="fixed inset-0 z-[100] bg-slate-950/20 backdrop-blur-md"
        aria-hidden="true"
    ></div>

    {{-- Notification stays sharp above the blurred page --}}
    <div
        class="fixed inset-0 z-[101] flex items-center justify-center overflow-y-auto p-4"
        role="dialog"
        aria-modal="true"
        aria-labelledby="shop-lock-title"
    >
        <section class="w-full max-w-lg rounded-3xl border border-white/70 bg-white p-7 shadow-2xl sm:p-9">

            <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                <svg
                    class="h-9 w-9"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    aria-hidden="true"
                >
                    <circle cx="12" cy="12" r="9"></circle>
                    <path
                        d="M12 7v5l3 2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    ></path>
                </svg>
            </div>

            @if ($shopLockApplication->status === 'pending')
                <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-amber-700">
                    Under Review
                </span>

                <h2
                    id="shop-lock-title"
                    class="mt-4 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl"
                >
                    Your shop application is under review
                </h2>

                <p class="mt-4 text-sm leading-7 text-gray-600">
                    Your application has been submitted successfully.
                    Our admin team needs to approve your shop before you
                    can access the seller dashboard and shop management
                    features.
                </p>
            @else
                <span class="inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-bold uppercase tracking-wider text-red-700">
                    Access Locked
                </span>

                <h2
                    id="shop-lock-title"
                    class="mt-4 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl"
                >
                    Your shop is not approved yet
                </h2>

                <p class="mt-4 text-sm leading-7 text-gray-600">
                    Your application status is
                    <strong>{{ ucfirst($shopLockApplication->status) }}</strong>.
                    Seller features will remain locked until the application
                    is approved.
                </p>

                @if ($shopLockApplication->rejection_reason)
                    <div class="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-800">
                        <strong>Admin feedback:</strong>
                        {{ $shopLockApplication->rejection_reason }}
                    </div>
                @endif
            @endif

            <div class="mt-6 rounded-2xl border border-gray-100 bg-gray-50 p-4">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500">
                            Shop name
                        </p>
                        <p class="mt-1 font-semibold text-gray-900">
                            {{ $shopLockApplication->shop_name }}
                        </p>
                    </div>

                    <span class="shrink-0 rounded-lg bg-white px-3 py-2 text-xs font-bold uppercase text-gray-700 ring-1 ring-gray-200">
                        {{ str_replace('_', ' ', $shopLockApplication->status) }}
                    </span>
                </div>
            </div>

            <p class="mt-5 text-center text-xs leading-5 text-gray-500">
                This page will check for an approval update automatically.
                Please keep this page open.
            </p>

            <form method="POST" action="{{ route('logout') }}" class="mt-6">
                @csrf

                <button
                    type="submit"
                    class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    Log out
                </button>
            </form>
        </section>
    </div>
@endif


<body class="font-sans antialiased {{ request()->routeIs('customer.dashboard') ? 'customer-dashboard-page' : '' }}">    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <!-- Page Heading -->
  @isset($header)
    <header @class([
        'bg-white shadow' => !request()->routeIs('customer.dashboard'),
        'sticky top-16 z-40 border-b border-gray-200/80 bg-white/95 shadow-sm backdrop-blur-xl'
            => request()->routeIs('customer.dashboard'),
    ])>
        <div class="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
            {{ $header }}
        </div>
    </header>
@endisset

        <!-- Page Content -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>

</html>
