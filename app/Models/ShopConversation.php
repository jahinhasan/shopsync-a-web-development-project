<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ShopConversation extends Model
{
    protected $fillable = [
        'customer_id',
        'shop_owner_id',
        'product_id',
        'product_name',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function shopOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shop_owner_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class, 'conversation_id')
            ->latestOfMany();
    }
}