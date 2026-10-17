<?php
use App\Models\ShopConversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel(
    'chat.conversation.{conversationId}',
    function (User $user, int $conversationId): bool {
        $conversation = ShopConversation::find($conversationId);

        if (! $conversation) {
            return false;
        }

        return (
            $user->isCustomer()
            && (int) $conversation->customer_id === (int) $user->id
        ) || (
            $user->isBusinessOwner()
            && (int) $conversation->shop_owner_id === (int) $user->id
        );
    }
);