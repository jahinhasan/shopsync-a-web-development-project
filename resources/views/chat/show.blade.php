<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-indigo-600">
                    ShopSync Messages
                </p>
                <h1 class="mt-1 text-xl font-bold text-gray-900">
                    {{ auth()->user()->isCustomer()
                        ? $conversation->shopOwner->name
                        : $conversation->customer->name }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Product: {{ $conversation->product_name }}
                </p>
            </div>

            <a href="{{ route('chat.index') }}"
               class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                All messages
            </a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8"
         id="chat-app"
         data-conversation-id="{{ $conversation->id }}"
         data-current-user-id="{{ auth()->id() }}"
         data-message-url="{{ route('chat.messages.store', $conversation) }}">

        <div id="chat-messages"
             aria-live="polite"
             class="flex h-[55vh] min-h-80 flex-col gap-3 overflow-y-auto rounded-3xl border border-gray-200 bg-slate-50 p-4 sm:p-6">
            @foreach ($messages as $message)
                @php
                    $mine = (int) $message->sender_id === (int) auth()->id();
                @endphp

                <div data-message-id="{{ $message->id }}"
                     class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[85%] rounded-2xl px-4 py-3 shadow-sm sm:max-w-[75%] {{ $mine ? 'rounded-br-md bg-indigo-600 text-white' : 'rounded-bl-md border border-gray-200 bg-white text-gray-900' }}">
                        @if (!$mine)
                            <p class="mb-1 text-xs font-bold text-indigo-600">
                                {{ $message->sender_name }}
                            </p>
                        @endif

                        <p class="whitespace-pre-wrap break-words text-sm">{{ $message->body }}</p>

                        <time class="mt-2 block text-right text-[10px] {{ $mine ? 'text-indigo-100' : 'text-gray-400' }}">
                            {{ $message->created_at->format('M d, H:i') }}
                        </time>
                    </div>
                </div>
            @endforeach
        </div>

        <div id="chat-error"
             role="alert"
             class="mt-3 hidden rounded-xl bg-red-50 p-3 text-sm text-red-700"></div>

        <form id="chat-form"
              method="POST"
              action="{{ route('chat.messages.store', $conversation) }}"
              class="mt-4 flex items-end gap-3 rounded-2xl border border-gray-200 bg-white p-3 shadow-sm">
            @csrf

            <label for="chat-body" class="sr-only">Message</label>
            <textarea id="chat-body"
                      name="body"
                      rows="2"
                      required
                      maxlength="4000"
                      placeholder="Type your message..."
                      class="max-h-36 min-h-12 flex-1 resize-y rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>

            <button id="chat-send"
                    type="submit"
                    class="inline-flex min-h-12 items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-50">
                Send
            </button>
        </form>

        <p class="mt-3 text-xs text-gray-400">
            Keep communication and transaction details within ShopSync.
        </p>
    </div>

    <script>
        (() => {
            const app = document.getElementById('chat-app');
            const list = document.getElementById('chat-messages');
            const form = document.getElementById('chat-form');
            const input = document.getElementById('chat-body');
            const button = document.getElementById('chat-send');
            const errorBox = document.getElementById('chat-error');

            if (!app || !list || !form) return;

            const userId = Number(app.dataset.currentUserId);
            const conversationId = app.dataset.conversationId;

            function appendMessage(message) {
                if (list.querySelector(`[data-message-id="${message.id}"]`)) return;

                const mine = Number(message.sender_id) === userId;
                const row = document.createElement('div');
                row.dataset.messageId = message.id;
                row.className = `flex ${mine ? 'justify-end' : 'justify-start'}`;

                const bubble = document.createElement('div');
                bubble.className = `max-w-[85%] rounded-2xl px-4 py-3 shadow-sm sm:max-w-[75%] ${
                    mine
                        ? 'rounded-br-md bg-indigo-600 text-white'
                        : 'rounded-bl-md border border-gray-200 bg-white text-gray-900'
                }`;

                if (!mine) {
                    const name = document.createElement('p');
                    name.className = 'mb-1 text-xs font-bold text-indigo-600';
                    name.textContent = message.sender_name;
                    bubble.appendChild(name);
                }

                const body = document.createElement('p');
                body.className = 'whitespace-pre-wrap break-words text-sm';
                body.textContent = message.body;
                bubble.appendChild(body);

                const time = document.createElement('time');
                time.className = `mt-2 block text-right text-[10px] ${
                    mine ? 'text-indigo-100' : 'text-gray-400'
                }`;

                const date = new Date(message.created_at);
                time.textContent = Number.isNaN(date.getTime())
                    ? ''
                    : date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                bubble.appendChild(time);
                row.appendChild(bubble);
                list.appendChild(row);
                list.scrollTop = list.scrollHeight;
            }

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const body = input.value.trim();
                if (!body) return;

                errorBox.textContent = '';
                errorBox.classList.add('hidden');
                button.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({ body }),
                    });

                    const result = await response.json();

                    if (!response.ok) {
                        const message = result.message
                            || Object.values(result.errors || {}).flat().join(' ')
                            || 'Unable to send your message.';
                        throw new Error(message);
                    }

                    appendMessage(result.message);
                    input.value = '';
                    input.focus();
                } catch (error) {
                    errorBox.textContent = error.message || 'Message could not be sent.';
                    errorBox.classList.remove('hidden');
                } finally {
                    button.disabled = false;
                }
            });

            list.scrollTop = list.scrollHeight;

            if (window.Echo) {
                window.Echo.private(`chat.conversation.${conversationId}`)
                    .listen('.message.sent', (message) => appendMessage(message));
            } else {
                console.warn('Laravel Echo is unavailable. Messages will appear after refresh until broadcasting is configured.');
            }
        })();
    </script>
</x-app-layout>