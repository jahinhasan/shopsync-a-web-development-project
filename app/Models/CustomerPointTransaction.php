<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPointTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'order_item_id',
        'customer_voucher_id',
        'type',
        'points',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderIdem::class, 'order_item_id');
    }

    public function customerVoucher(): BelongsTo
    {
        return $this->belongsTo(CustomerVoucher::class);
    }
}