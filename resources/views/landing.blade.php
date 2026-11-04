<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="ShopSync is a multi-vendor marketplace: independent sellers run their own shops, shoppers get one cart, real-time chat, order tracking and reward points.">
    <title>{{ config('app.name', 'ShopSync') }} — One marketplace. Every shop in sync.</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/landing.css', 'resources/js/landing.js'])
</head>

<body class="bg-white font-sans text-gray-900 antialiased">

    {{-- ============ Header ============ --}}
    <header class="absolute inset-x-0 top-0 z-20">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-black text-white shadow-lg shadow-indigo-600/40">
                    S
                </span>
                <span class="text-lg font-bold tracking-tight text-white">
                    Shop<span class="text-indigo-300">Sync</span>
                </span>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <a href="{{ route('login') }}"
                    class="rounded-xl px-4 py-2 text-sm font-semibold text-indigo-100 transition hover:bg-white/10 hover:text-white">
                    Log in
                </a>
                <a href="{{ route('register') }}"
                    class="rounded-xl bg-indigo-500 px-4 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-900/40 transition hover:bg-indigo-400">
                    Open your shop
                </a>
            </div>
        </div>
    </header>

    {{-- ============ Hero: flow field + shop-sync network, drawn from
         sine/cosine vector fields and quadratic Bézier curves ============ --}}
    <section class="landing-hero relative overflow-hidden">
        <canvas id="landing-hero-canvas" class="absolute inset-0 h-full w-full" aria-hidden="true"></canvas>

        <div class="relative z-10 mx-auto flex max-w-7xl flex-col items-center px-4 pb-24 pt-36 text-center sm:px-6 sm:pb-32 sm:pt-44 lg:px-8">
            <p data-landing-reveal class="text-xs font-bold uppercase tracking-[0.25em] text-indigo-300">
                ShopSync marketplace
            </p>

            <h1 data-landing-reveal data-reveal-delay="1"
                class="mt-5 max-w-3xl text-4xl font-extrabold tracking-tight text-white sm:text-6xl">
                One marketplace.
                <span class="bg-gradient-to-r from-indigo-300 via-violet-300 to-fuchsia-300 bg-clip-text text-transparent">
                    Every shop in sync.
                </span>
            </h1>

            <p data-landing-reveal data-reveal-delay="2"
                class="mt-6 max-w-2xl text-base leading-7 text-indigo-100/90 sm:text-lg sm:leading-8">
                Independent sellers open their own stores, shoppers get one cart for all of them —
                with real-time chat, order tracking and reward points on every delivery.
            </p>

            <div data-landing-reveal data-reveal-delay="3" class="mt-9 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('register') }}"
                    class="rounded-xl bg-white px-7 py-3.5 text-sm font-bold text-indigo-700 shadow-xl shadow-indigo-950/40 transition hover:-translate-y-0.5 hover:bg-indigo-50">
                    Create your account
                </a>
                <a href="{{ route('login') }}"
                    class="rounded-xl border border-white/25 px-7 py-3.5 text-sm font-bold text-white transition hover:bg-white/10">
                    Log in
                </a>
            </div>

            <p data-landing-reveal data-reveal-delay="4"
                class="mt-10 inline-flex max-w-xl items-center gap-2 rounded-full border border-white/15 bg-white/5 px-4 py-2 text-xs font-medium text-indigo-200/90">
                <svg aria-hidden="true" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 18v-7a4 4 0 0 1 8 0m0 7V5m8 13v-4a3 3 0 0 0-6 0" stroke-linecap="round" />
                </svg>
                Every animation on this page is drawn live from equations — no video, no GIFs.
            </p>
        </div>
    </section>

    {{-- ============ Feature trio ============ --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
        <div class="grid gap-6 md:grid-cols-3">
            <div data-landing-reveal class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 9h18M6 18h12M5 9l1.5-4.5h11L19 9m-14 0v10h14V9M9 13h6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <h2 class="mt-4 text-lg font-bold text-gray-900">Many shops, one checkout</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Every seller runs their own storefront with live stock. You fill a single cart
                    across all of them and check out once.
                </p>
            </div>

            <div data-landing-reveal data-reveal-delay="1" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="8" />
                        <path d="M12 8v8m3-6.5c0-.8-1.3-1.5-3-1.5s-3 .7-3 1.5 1.3 1.5 3 1.5 3 .7 3 1.5-1.3 1.5-3 1.5-3-.7-3-1.5" stroke-linecap="round" />
                    </svg>
                </span>
                <h2 class="mt-4 text-lg font-bold text-gray-900">Earn as you shop</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Delivered orders earn reward points automatically. Trade points for discount
                    vouchers and use them on your next order.
                </p>
            </div>

            <div data-landing-reveal data-reveal-delay="2" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a8 8 0 0 1-11.6 7.1L4 21l1.9-5.4A8 8 0 1 1 21 12Z" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <h2 class="mt-4 text-lg font-bold text-gray-900">Talk before you buy</h2>
                <p class="mt-2 text-sm leading-6 text-gray-500">
                    Message sellers in real time, then follow every order from payment to delivery
                    with notifications along the way.
                </p>
            </div>
        </div>
    </section>

    {{-- ============ How it works: each step is a live parametric curve ============ --}}
    <section class="border-y border-gray-100 bg-gray-50">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p data-landing-reveal class="text-xs font-bold uppercase tracking-[0.25em] text-indigo-600">
                    How it works
                </p>
                <h2 data-landing-reveal data-reveal-delay="1" class="mt-3 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                    Three curves, one marketplace
                </h2>
                <p data-landing-reveal data-reveal-delay="2" class="mt-4 text-base leading-7 text-gray-500">
                    Each panel below is a canvas redrawing itself every frame from a single
                    parametric equation.
                </p>
            </div>

            <div class="mt-12 grid gap-6 lg:grid-cols-3">
                <div data-landing-reveal class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <canvas id="landing-rose-canvas" class="landing-demo-canvas" aria-hidden="true"></canvas>
                    <h3 class="mt-4 text-base font-bold text-gray-900">1 — Open your shop</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-500">
                        Apply as a seller, get approved by our team, and your catalog blooms —
                        drawn here as a five-petaled rose curve.
                    </p>
                    <p class="landing-equation">r = R · cos(5θ)</p>
                </div>

                <div data-landing-reveal data-reveal-delay="1" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <canvas id="landing-wave-canvas" class="landing-demo-canvas" aria-hidden="true"></canvas>
                    <h3 class="mt-4 text-base font-bold text-gray-900">2 — Sell in sync</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-500">
                        Stock, orders and payments stay aligned across buyer, seller and admin
                        dashboards — two signals and their superposition.
                    </p>
                    <p class="landing-equation">y = A sin(k₁x − ω₁t) + ½A sin(k₂x + ω₂t)</p>
                </div>

                <div data-landing-reveal data-reveal-delay="2" class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <canvas id="landing-growth-canvas" class="landing-demo-canvas" aria-hidden="true"></canvas>
                    <h3 class="mt-4 text-base font-bold text-gray-900">3 — Grow with rewards</h3>
                    <p class="mt-2 text-sm leading-6 text-gray-500">
                        Points turn first orders into regular ones — growth that follows the
                        classic S-shaped logistic curve.
                    </p>
                    <p class="landing-equation">y = L / (1 + e<sup>−k(x − x₀)</sup>)</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ============ CTA band with animated signal waves ============ --}}
    <section class="landing-cta relative overflow-hidden">
        <canvas id="landing-signals-canvas" class="absolute inset-x-0 top-0 h-32 w-full sm:h-40" aria-hidden="true"></canvas>

        <div class="relative z-10 mx-auto max-w-7xl px-4 py-20 text-center sm:px-6 sm:py-28 lg:px-8">
            <h2 data-landing-reveal class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                Ready to sync?
            </h2>
            <p data-landing-reveal data-reveal-delay="1" class="mx-auto mt-4 max-w-xl text-base leading-7 text-indigo-100/90">
                Join as a shopper or open your own shop — either way, everything stays in sync
                from your very first order.
            </p>
            <div data-landing-reveal data-reveal-delay="2" class="mt-9 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('register') }}"
                    class="rounded-xl bg-white px-7 py-3.5 text-sm font-bold text-indigo-700 shadow-xl shadow-indigo-950/40 transition hover:-translate-y-0.5 hover:bg-indigo-50">
                    Create your account
                </a>
                <a href="{{ route('login') }}"
                    class="rounded-xl border border-white/25 px-7 py-3.5 text-sm font-bold text-white transition hover:bg-white/10">
                    Log in
                </a>
            </div>
        </div>
    </section>

    {{-- ============ Footer ============ --}}
    <footer class="border-t border-white/10 bg-[#0b0f2a]">
        <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-8 sm:flex-row sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-sm font-black text-white">
                    S
                </span>
                <span class="text-sm font-semibold text-white">
                    Shop<span class="text-indigo-300">Sync</span>
                </span>
            </div>

            <p class="text-xs text-indigo-200/70">
                &copy; {{ date('Y') }} ShopSync — a marketplace where every shop stays in sync.
            </p>

            <div class="flex items-center gap-4 text-sm font-semibold text-indigo-200">
                <a href="{{ route('login') }}" class="transition hover:text-white">Log in</a>
                <a href="{{ route('register') }}" class="transition hover:text-white">Register</a>
            </div>
        </div>
    </footer>

</body>

</html>
