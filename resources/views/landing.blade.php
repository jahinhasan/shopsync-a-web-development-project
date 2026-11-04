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

<body class="bg-[#0b0f2a] font-sans text-indigo-100 antialiased">

    {{-- Full-page animated backdrop: a fixed gradient plus a canvas running
         a sine/cosine vector field that swirls around the visitor's cursor --}}
    <div class="landing-backdrop fixed inset-0 z-0" aria-hidden="true"></div>
    <canvas id="landing-bg-canvas" class="fixed inset-0 z-0 h-full w-full" aria-hidden="true"></canvas>
    <canvas id="landing-cursor-canvas" class="landing-cursor-layer fixed inset-0 z-40 h-full w-full" aria-hidden="true"></canvas>

    {{-- ============ Header ============ --}}
    <header class="relative z-20">
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

    <main class="relative z-10">
        {{-- ============ Hero: shop-sync network over the shared backdrop ============ --}}
        <section class="relative overflow-hidden">
            <canvas id="landing-hero-canvas" class="absolute inset-0 h-full w-full" aria-hidden="true"></canvas>

            <div class="relative z-10 mx-auto flex max-w-7xl flex-col items-center px-4 pb-24 pt-32 text-center sm:px-6 sm:pb-32 sm:pt-44 lg:px-8">
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
            </div>
        </section>

        {{-- ============ Feature trio ============ --}}
        <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p data-landing-reveal class="text-xs font-bold uppercase tracking-[0.25em] text-indigo-300">
                    Why ShopSync
                </p>
                <h2 data-landing-reveal data-reveal-delay="1" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                    A marketplace that works like one
                </h2>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                <div data-landing-reveal class="landing-glass rounded-2xl p-6 transition hover:-translate-y-1">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-indigo-300">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9h18M6 18h12M5 9l1.5-4.5h11L19 9m-14 0v10h14V9M9 13h6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <h3 class="mt-4 text-lg font-bold text-white">Many shops, one checkout</h3>
                    <p class="mt-2 text-sm leading-6 text-indigo-200/80">
                        Every seller runs their own storefront with live stock. You fill a single cart
                        across all of them and check out once.
                    </p>
                </div>

                <div data-landing-reveal data-reveal-delay="1" class="landing-glass rounded-2xl p-6 transition hover:-translate-y-1">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-amber-300">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="8" />
                            <path d="M12 8v8m3-6.5c0-.8-1.3-1.5-3-1.5s-3 .7-3 1.5 1.3 1.5 3 1.5 3 .7 3 1.5-1.3 1.5-3 1.5-3-.7-3-1.5" stroke-linecap="round" />
                        </svg>
                    </span>
                    <h3 class="mt-4 text-lg font-bold text-white">Earn as you shop</h3>
                    <p class="mt-2 text-sm leading-6 text-indigo-200/80">
                        Delivered orders earn reward points automatically. Trade points for discount
                        vouchers and use them on your next order.
                    </p>
                </div>

                <div data-landing-reveal data-reveal-delay="2" class="landing-glass rounded-2xl p-6 transition hover:-translate-y-1">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-teal-300">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 12a8 8 0 0 1-11.6 7.1L4 21l1.9-5.4A8 8 0 1 1 21 12Z" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                    <h3 class="mt-4 text-lg font-bold text-white">Talk before you buy</h3>
                    <p class="mt-2 text-sm leading-6 text-indigo-200/80">
                        Message sellers in real time, then follow every order from payment to delivery
                        with notifications along the way.
                    </p>
                </div>
            </div>
        </section>

        {{-- ============ How it works: three live parametric panels ============ --}}
        <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 sm:pb-24 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p data-landing-reveal class="text-xs font-bold uppercase tracking-[0.25em] text-indigo-300">
                    How it works
                </p>
                <h2 data-landing-reveal data-reveal-delay="1" class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                    From first product to growing shop
                </h2>
                <p data-landing-reveal data-reveal-delay="2" class="mt-4 text-base leading-7 text-indigo-200/80">
                    Open a store, keep everything in sync, and let rewards bring customers back.
                </p>
            </div>

            <div class="mt-12 grid gap-6 lg:grid-cols-3">
                <div data-landing-reveal class="landing-glass rounded-2xl p-6">
                    <canvas id="landing-rose-canvas" class="landing-demo-canvas" aria-hidden="true"></canvas>
                    <h3 class="mt-4 text-base font-bold text-white">1 — Open your shop</h3>
                    <p class="mt-2 text-sm leading-6 text-indigo-200/80">
                        Apply as a seller, get approved by our team, and list your first products
                        in minutes.
                    </p>
                </div>

                <div data-landing-reveal data-reveal-delay="1" class="landing-glass rounded-2xl p-6">
                    <canvas id="landing-wave-canvas" class="landing-demo-canvas" aria-hidden="true"></canvas>
                    <h3 class="mt-4 text-base font-bold text-white">2 — Sell in sync</h3>
                    <p class="mt-2 text-sm leading-6 text-indigo-200/80">
                        Stock, orders and payments stay aligned across buyer, seller and admin
                        dashboards.
                    </p>
                </div>

                <div data-landing-reveal data-reveal-delay="2" class="landing-glass rounded-2xl p-6">
                    <canvas id="landing-growth-canvas" class="landing-demo-canvas" aria-hidden="true"></canvas>
                    <h3 class="mt-4 text-base font-bold text-white">3 — Grow with rewards</h3>
                    <p class="mt-2 text-sm leading-6 text-indigo-200/80">
                        Points turn first orders into regular ones and keep customers coming back.
                    </p>
                </div>
            </div>
        </section>

        {{-- ============ CTA band with animated signal waves ============ --}}
        <section class="relative">
            <canvas id="landing-signals-canvas" class="absolute inset-x-0 top-0 h-32 w-full sm:h-40" aria-hidden="true"></canvas>

            <div class="relative z-10 mx-auto max-w-7xl px-4 pb-20 pt-24 text-center sm:px-6 sm:pb-28 sm:pt-32 lg:px-8">
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
    </main>

    {{-- ============ Footer ============ --}}
    <footer class="relative z-10 border-t border-white/10">
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
