<?php

namespace App\Models;
use App\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderIdem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'unit_price',
        'quantity',
        'subtotal',
        'status',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
    protected static function booted(): void
{
    static::creating(function (OrderIdem $item) {
        if (! $item->product_id) {
            return;
        }

        $product = Product::withTrashed()->find($item->product_id);

        if (! $product) {
            return;
        }

        // Snapshot rewards so future product edits cannot alter an old order.
        $item->reward_points_per_unit = (int) $product->reward_points;
        $item->surprise_voucher_eligible = (bool) $product->surprise_voucher_enabled;
        $item->reward_category_id = $product->category_id;
    });
}
}
