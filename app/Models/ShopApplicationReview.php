<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopApplicationReview extends Model
{
    protected $fillable = [
        'shop_application_id',
        'actor_id',
        'actor_role',
        'action',
        'comment',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(ShopApplication::class, 'shop_application_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
