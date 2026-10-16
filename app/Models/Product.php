<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;
    protected $fillable = ['user_id','category_id','name','slug','description','price','stock','image_path','is_active'];
    protected function casts(): array{ return ['price'=>'decimal:2','is_active'=>'boolean'];}
    public function owner(): BelongsTo{return $this->belongsTo(User::class,'user_id');}
    public function category():\Illuminate\Database\Eloquent\Relations\BelongsTo{return $this->belongsTo(\App\Models\Category::class);}

public function scopeAvailableToCustomers(Builder $query): Builder
{
    return $query
        ->where('products.is_active', true)
        ->where('products.admin_status', 'active')
        ->where('products.stock', '>', 0)
        ->whereHas('owner.shopApplication', function (Builder $shop) {
            $shop->where('status', 'approved')
                ->where('shop_status', 'active');
        });
}

public function isAvailableToCustomers(): bool
{
    $this->loadMissing('owner.shopApplication');

    $shop = $this->owner?->shopApplication;

    return $this->is_active
        && $this->admin_status === 'active'
        && $this->stock > 0
        && $shop?->status === 'approved'
        && $shop?->shop_status === 'active';
}


    }
