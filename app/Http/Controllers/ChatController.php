<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\ChatMessage;
use App\Models\OrderIdem;
use App\Models\Product;
use App\Models\ShopConversation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function start(Request $request, Product $product): RedirectResponse
    {
        $customer = $request->user();

        abort_unless($customer->isCustomer(), 403);
        abort_unless($product->isAvailableToCustomers(), 404);
        abort_if((int) $product->user_id === (int) $customer->id, 403);

        $owner = $product->owner;

        abort_unless($owner && $owner->isBusinessOwner(), 404);

        $conversation = ShopConversation::firstOrCreate(
            [
                'customer_id' => $customer->id,
                'shop_owner_id' => $owner->id,
                'product_id' => $product->id,
            ],
            [
                'product_name' => $product->name,
            ]
        );

        return redirect()->route('chat.show', $conversation);
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        abort_unless(
            $user->isCustomer() || $user->isBusinessOwner(),
            403
        );

        $query = ShopConversation::query();

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } else {
            $query->where('shop_owner_id', $user->id);
        }

        $conversations = $query
            ->with(['customer', 'shopOwner', 'product', 'lastMessage'])
            ->withCount([
                'messages as unread_count' => fn ($messages) => $messages
                    ->whereNull('read_at')
                    ->where('sender_id', '!=', $user->id),
            ])
            ->orderByRaw('last_message_at IS NULL')
            ->orderByDesc('last_message_at')
            ->paginate(20);

        return view('chat.index', compact('conversations'));
    }

    public function show(
        Request $request,
        ShopConversation $conversation
    ): View {
        $this->authorizeConversation($request, $conversation);

        $user = $request->user();

        $conversation->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->update(['read_at' => now()]);

        $conversation->load(['customer', 'shopOwner', 'product']);

        $messages = $conversation->messages()
            ->with('sender')
            ->latest()
            ->limit(100)
            ->get()
            ->reverse()
            ->values();

        return view('chat.show', compact('conversation', 'messages'));
    }

    public function storeMessage(
        Request $request,
        ShopConversation $conversation
    ): JsonResponse {
        $this->authorizeConversation($request, $conversation);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:4000'],
        ]);

        $user = $request->user();

        $message = DB::transaction(function () use (
            $conversation,
            $user,
            $data
        ) {
            $message = $conversation->messages()->create([
                'sender_id' => $user->id,
                'sender_name' => $user->name,
                'body' => trim($data['body']),
            ]);

            $conversation->update([
                'last_message_at' => $message->created_at,
            ]);

            return $message;
        });

        broadcast(new ChatMessageSent($message))->toOthers();

        return response()->json([
            'message' => [
                'id' => $message->id,
                'conversation_id' => $conversation->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender_name,
                'body' => $message->body,
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ], 201);
    }

    private function authorizeConversation(
        Request $request,
        ShopConversation $conversation
    ): void {
        $user = $request->user();

        $isCustomer = $user->isCustomer()
            && (int) $conversation->customer_id === (int) $user->id;

        $isShopOwner = $user->isBusinessOwner()
            && (int) $conversation->shop_owner_id === (int) $user->id;

        abort_unless($isCustomer || $isShopOwner, 403);
    }
}