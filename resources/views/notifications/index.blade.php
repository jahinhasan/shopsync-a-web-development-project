
<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
        <p class="mt-1 text-sm text-gray-500">
            Moderation decisions, warnings, and account updates.
        </p>
    </x-slot>

    <div class="min-h-[70vh] bg-gray-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-4xl space-y-5">

            @if (session('success'))
                <div class="rounded-xl bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    @method('PATCH')

                    <button class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        Mark all as read
                    </button>
                </form>
            @endif

            @forelse ($notifications as $notification)
                <article class="rounded-2xl border p-5 shadow-sm
                    {{ $notification->read_at ? 'border-gray-200 bg-white' : 'border-indigo-200 bg-indigo-50/50' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="font-semibold text-gray-900">
                                {{ $notification->data['title'] ?? 'ShopSync notification' }}
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-gray-700">
                                {{ $notification->data['message'] ?? '' }}
                            </p>

                            @if (!empty($notification->data['reason']))
                                <div class="mt-3 rounded-xl bg-white p-4">
                                    <p class="text-xs font-semibold uppercase text-gray-500">
                                        Admin message
                                    </p>
                                    <p class="mt-2 whitespace-pre-line text-sm text-gray-700">
                                        {{ $notification->data['reason'] }}
                                    </p>
                                </div>
                            @endif

                            <p class="mt-3 text-xs text-gray-500">
                                {{ $notification->created_at->format('d M Y, h:i A') }}
                                @if (!$notification->read_at)
                                    · Unread
                                @endif
                            </p>
                        </div>

                        @if (!$notification->read_at)
                            <form method="POST"
                                  action="{{ route('notifications.read', $notification->id) }}">
                                @csrf
                                @method('PATCH')

                                <button class="shrink-0 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700">
                                    Mark read
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center">
                    <h2 class="font-semibold text-gray-900">You're all caught up</h2>
                    <p class="mt-2 text-sm text-gray-500">
                        Important moderation notices will appear here.
                    </p>
                </div>
            @endforelse

            {{ $notifications->links() }}
        </div>
    </div>
</x-app-layout>
