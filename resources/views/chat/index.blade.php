<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-gray-900">Messages</h1>
        <p class="mt-1 text-sm text-gray-500">
            Conversations with customers and shops.
        </p>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
        @forelse ($conversations as $conversation)
            @php
                $otherUser = auth()->user()->isCustomer()
                    ? $conversation->shopOwner
                    : $conversation->customer;
            @endphp

            <a href="{{ route('chat.show', $conversation) }}"
               class="flex items-center gap-4 rounded-2xl border border-gray-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-lg">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 font-bold text-white">
                    {{ strtoupper(mb_substr($otherUser?->name ?? '?', 0, 1)) }}
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block truncate font-bold text-gray-900">
                        {{ $otherUser?->name ?? 'Account unavailable' }}
                    </span>

                    <span class="mt-1 block truncate text-xs text-indigo-600">
                        Product: {{ $conversation->product_name }}
                    </span>

                    <span class="mt-2 block truncate text-sm text-gray-500">
                        {{ $conversation->lastMessage?->body ?? 'Start your conversation' }}
                    </span>
                </span>

                @if ($conversation->unread_count > 0)
                    <span class="flex h-7 min-w-7 items-center justify-center rounded-full bg-indigo-600 px-2 text-xs font-bold text-white">
                        {{ $conversation->unread_count }}
                    </span>
                @endif
            </a>
        @empty
            <div class="rounded-3xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center">
                <h2 class="text-lg font-bold text-gray-900">
                    No conversations yet
                </h2>
                <p class="mt-2 text-sm text-gray-500">
                    Open a product and start chatting with its seller.
                </p>
                <a href="{{ route('shop.index') }}"
                   class="mt-6 inline-flex rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                    Explore products
                </a>
            </div>
        @endforelse

        {{ $conversations->links() }}
    </div>
</x-app-layout>