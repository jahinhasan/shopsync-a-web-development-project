@php
    $user = Auth::user();

    $isBusinessOwner = $user?->role === 'business_owner';

    $shopApplication = $isBusinessOwner ? $user->shopApplication : null;

    $isOwnerApproved = $isBusinessOwner && $shopApplication?->status === 'approved';

    $applicationUrl = $shopApplication
        ? route('owner.shop-application.status')
        : route('owner.shop-application.create');
@endphp

<nav x-data="{ open: false }" class="ss-navbar sticky top-0 z-50 border-b border-gray-200/80 bg-white/90 backdrop-blur-xl">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <div class="flex min-w-0 items-center gap-8">
                <a href="{{ $isBusinessOwner && !$isOwnerApproved ? $applicationUrl : route('dashboard') }}"
                    class="ss-brand flex shrink-0 items-center gap-3">
                    <span
                        class="ss-brand-mark flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-black text-white">
                        S
                    </span>
                    <span class="text-lg font-bold tracking-tight text-gray-900">
                        Shop<span class="text-indigo-600">Sync</span>
                    </span>
                </a>

                <div class="hidden items-center gap-2 sm:flex">
                    @if (!$isBusinessOwner || $isOwnerApproved)
                        <a href="{{ route('dashboard') }}" @class([
                            'ss-nav-link rounded-xl px-4 py-2 text-sm font-semibold',
                            'is-active' => request()->routeIs('dashboard'),
                            'text-gray-600 hover:text-indigo-700' => !request()->routeIs('dashboard'),
                        ])>
                            Dashboard
                        </a>

                        @if (Route::has('shop.index'))
                            <a href="{{ route('shop.index') }}" @class([
                                'ss-nav-link rounded-xl px-4 py-2 text-sm font-semibold',
                                'is-active' => request()->routeIs('shop.*'),
                                'text-gray-600 hover:text-indigo-700' => !request()->routeIs('shop.*'),
                            ])>
                                Shop
                            </a>
                        @endif
@if (Auth::user()?->role === 'admin')
    <a href="{{ route('admin.moderation.index') }}"
       class="ss-nav-link rounded-xl px-4 py-2 text-sm font-semibold text-gray-600 hover:text-indigo-700">
        Moderation Center
    </a>
@endif
                        <a href="{{ route('notifications.index') }}"
   class="ss-nav-link rounded-xl px-4 py-2 text-sm font-semibold text-gray-600 hover:text-indigo-700">
    Notifications

    @if (Auth::user()->unreadNotifications()->exists())
        <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">
            New
        </span>
    @endif
</a>
                        @if (Auth::user()?->role === 'admin')
                            <a href="{{ route('admin.shop-applications.index') }}" @class([
                                'ss-nav-link rounded-xl px-4 py-2 text-sm font-semibold',
                                'is-active' => request()->routeIs('admin.shop-applications.*'),
                                'text-gray-600 hover:text-indigo-700' => !request()->routeIs(
                                    'admin.shop-applications.*'),
                            ])>
                                Shop Applications
                            </a>
                        @endif
                    @else
                        <a href="{{ $applicationUrl }}" @class([
                            'ss-nav-link rounded-xl px-4 py-2 text-sm font-semibold',
                            'is-active' => request()->routeIs('owner.shop-application.*'),
                            'text-gray-600 hover:text-indigo-700' => !request()->routeIs(
                                'owner.shop-application.*'),
                        ])>
                            {{ $shopApplication ? 'Application Status' : 'Shop Application' }}
                        </a>
                    @endif
                </div>
            </div>

            <div class="hidden items-center sm:flex">
                <div x-data="{ profileOpen: false }" class="relative" @keydown.escape.window="profileOpen = false">
                    <button type="button" @click="profileOpen = !profileOpen" :aria-expanded="profileOpen.toString()"
                        class="ss-profile-trigger flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-3 py-2 text-left">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-50 text-sm font-bold text-indigo-700">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>

                        <span class="max-w-36">
                            <span class="block truncate text-sm font-semibold text-gray-800">
                                {{ Auth::user()->name }}
                            </span>
                            <span class="block text-xs text-gray-500">My account</span>
                        </span>

                        <svg aria-hidden="true" class="h-4 w-4 text-gray-500 transition-transform duration-200"
                            :class="{ 'rotate-180': profileOpen }" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.09 1.032l-4.25 4.51a.75.75 0 0 1-1.09 0l-4.25-4.51a.75.75 0 0 1 .02-1.052Z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div x-cloak x-show="profileOpen" x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 translate-y-2 scale-95" @click.outside="profileOpen = false"
                        class="ss-profile-menu absolute right-0 mt-3 w-60 origin-top-right rounded-2xl border border-gray-200 bg-white p-2 shadow-xl">
                        <div class="border-b border-gray-100 px-3 py-3">
                            <p class="truncate text-sm font-semibold text-gray-900">
                                {{ Auth::user()->name }}
                            </p>
                            <p class="truncate text-xs text-gray-500">
                                {{ Auth::user()->email }}
                            </p>
                        </div>

                        <a href="{{ route('profile.edit') }}"
                            class="ss-menu-item mt-2 block rounded-xl px-3 py-2.5 text-sm text-gray-700">
                            Profile settings
                        </a>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="ss-menu-item block w-full rounded-xl px-3 py-2.5 text-left text-sm text-red-600">
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <button type="button" @click="open = !open" :aria-expanded="open.toString()"
                aria-label="Toggle navigation menu"
                class="ss-mobile-toggle inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-700 sm:hidden">
                <svg x-show="!open" aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8">
                    <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round" />
                </svg>

                <svg x-cloak x-show="open" aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="1.8">
                    <path d="m6 6 12 12M18 6 6 18" stroke-linecap="round" />
                </svg>
            </button>
        </div>
    </div>

    <div x-cloak x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="ss-mobile-menu border-t border-gray-100 bg-white px-4 pb-4 pt-3 sm:hidden">

        @if (!$isBusinessOwner || $isOwnerApproved)
            <a href="{{ route('dashboard') }}" @class([
                'ss-nav-link block rounded-xl px-4 py-3 text-sm font-semibold',
                'is-active' => request()->routeIs('dashboard'),
                'text-gray-700' => !request()->routeIs('dashboard'),
            ])>
                Dashboard
            </a>

            @if (Route::has('shop.index'))
                <a href="{{ route('shop.index') }}" @class([
                    'ss-nav-link mt-1 block rounded-xl px-4 py-3 text-sm font-semibold',
                    'is-active' => request()->routeIs('shop.*'),
                    'text-gray-700' => !request()->routeIs('shop.*'),
                ])>
                    Shop
                </a>
            @endif
@if (Auth::user()?->role === 'admin')
    <a href="{{ route('admin.moderation.index') }}"
       class="ss-nav-link rounded-xl px-4 py-2 text-sm font-semibold text-gray-600 hover:text-indigo-700">
        Moderation Center
    </a>
@endif
<a href="{{ route('notifications.index') }}"
   class="ss-nav-link rounded-xl px-4 py-2 text-sm font-semibold text-gray-600 hover:text-indigo-700">
    Notifications

    @if (Auth::user()->unreadNotifications()->exists())
        <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">
            New
        </span>
    @endif
</a>
            @if (Auth::user()?->role === 'admin')
                <a href="{{ route('admin.shop-applications.index') }}" @class([
                    'ss-nav-link mt-1 block rounded-xl px-4 py-3 text-sm font-semibold',
                    'is-active' => request()->routeIs('admin.shop-applications.*'),
                    'text-gray-700' => !request()->routeIs('admin.shop-applications.*'),
                ])>
                    Shop Applications
                </a>
            @endif
        @else
            <a href="{{ $applicationUrl }}" @class([
                'ss-nav-link block rounded-xl px-4 py-3 text-sm font-semibold',
                'is-active' => request()->routeIs('owner.shop-application.*'),
                'text-gray-700' => !request()->routeIs('owner.shop-application.*'),
            ])>
                {{ $shopApplication ? 'Application Status' : 'Shop Application' }}
            </a>
        @endif


        <div class="mt-3 border-t border-gray-100 pt-3">
            <p class="px-3 text-sm font-semibold text-gray-900">
                {{ Auth::user()->name }}
            </p>
            <p class="mt-1 px-3 text-xs text-gray-500">
                {{ Auth::user()->email }}
            </p>

            <a href="{{ route('profile.edit') }}"
                class="ss-menu-item mt-3 block rounded-xl px-3 py-2.5 text-sm text-gray-700">
                Profile settings
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="ss-menu-item mt-1 block w-full rounded-xl px-3 py-2.5 text-left text-sm text-red-600">
                    Log out
                </button>
            </form>
        </div>
    </div>
</nav>
