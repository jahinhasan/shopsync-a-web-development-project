<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerVoucher extends Model
{
    protected $fillable = [
        'voucher_id',
        'user_id',
        'code',
        'source',
        'source_order_item_id',
        'status',
        'expires_at',
        'redeemed_order_id',
        'redeemed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'redeemed_at' => 'datetime',
        ];
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceOrderItem(): BelongsTo
    {
        return $this->belongsTo(OrderIdem::class, 'source_order_item_id');
    }

    public function redeemedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'redeemed_order_id');
    }
}