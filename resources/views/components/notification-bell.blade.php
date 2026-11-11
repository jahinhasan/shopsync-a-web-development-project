{{--
    Navbar notification bell: icon button with an unread-count badge and an
    Alpine dropdown listing the most recent notifications. Each entry shows
    its title, message and timestamp, highlights while unread, and links to
    the relevant page (order notifications deep-link to the order on the
    manage-orders page via their `url` payload). Marking read reuses the
    existing PATCH notification routes; the badge count refreshes on the
    resulting page reload.
--}}
@props([
    'limit' => 5,
])

@php
    $bellUser = auth()->user();
    $unreadCount = $bellUser->unreadNotifications()->count();
    $recentNotifications = $bellUser->notifications()
        ->latest()
        ->take($limit)
        ->get();
@endphp

<div x-data="{ bellOpen: false }" class="relative" @keydown.escape.window="bellOpen = false">
    <button type="button"
            @click="bellOpen = !bellOpen"
            :aria-expanded="bellOpen.toString()"
            aria-label="Notifications{{ $unreadCount > 0 ? ' ('.$unreadCount.' unread)' : '' }}"
            class="ss-nav-link relative inline-flex h-10 w-10 items-center justify-center rounded-xl text-gray-600 hover:text-indigo-700">
        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.8">
            <path d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"
                  stroke-linecap="round" stroke-linejoin="round"/>
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold leading-none text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-cloak x-show="bellOpen"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-2 scale-95"
         @click.outside="bellOpen = false"
         class="absolute right-0 z-50 mt-2 w-80 max-w-[calc(100vw-2rem)] origin-top-right rounded-2xl border border-gray-200 bg-white p-2 shadow-xl">

        <div class="flex items-center justify-between gap-2 border-b border-gray-100 px-3 py-2.5">
            <p class="text-sm font-bold text-gray-900">
                Notifications
                @if ($unreadCount > 0)
                    <span class="ml-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-bold text-red-700">
                        {{ $unreadCount }}
                    </span>
                @endif
            </p>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')

                    <button type="submit"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        Mark all as read
                    </button>
                </form>
            @endif
        </div>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($recentNotifications as $notification)
                <div class="relative rounded-xl {{ $notification->read_at ? '' : 'bg-indigo-50/70' }}">
                    <a href="{{ $notification->data['url'] ?? route('notifications.index') }}"
                       class="block px-3 py-2.5">
                        <p class="text-sm font-semibold text-gray-900">
                            @if (! $notification->read_at)
                                <span class="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-indigo-500 align-middle"
                                      aria-hidden="true"></span>
                            @endif
                            {{ $notification->data['title'] ?? 'ShopSync notification' }}
                        </p>

                        <p class="mt-0.5 truncate text-xs leading-5 text-gray-600">
                            {{ $notification->data['message'] ?? '' }}
                        </p>

                        <p class="mt-1 text-[11px] text-gray-400">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </a>

                    @if (! $notification->read_at)
                        <form method="POST"
                              action="{{ route('notifications.read', $notification->id) }}"
                              class="absolute right-2 top-2 z-10">
                            @csrf
                            @method('PATCH')

                            <button type="submit" title="Mark as read"
                                    aria-label="Mark as read"
                                    class="inline-flex h-6 w-6 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-400 hover:text-indigo-600">
                                <svg aria-hidden="true" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2">
                                    <path d="m4.5 12.75 6 6 9-13.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="px-3 py-6 text-center text-sm text-gray-500">
                    No notifications yet.
                </p>
            @endforelse
        </div>

        <a href="{{ route('notifications.index') }}"
           class="block border-t border-gray-100 px-3 py-2.5 text-center text-xs font-semibold text-indigo-600 hover:text-indigo-800">
            View all notifications →
        </a>
    </div>
</div>
