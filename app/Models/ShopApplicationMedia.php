<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopApplicationMedia extends Model
{
        protected $fillable = [
        'shop_application_id',
        'category',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

       public function application(): BelongsTo
    {
        return $this->belongsTo(ShopApplication::class, 'shop_application_id');
    }
}
