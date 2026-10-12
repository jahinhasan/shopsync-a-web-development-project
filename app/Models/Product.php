<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = ['user_id','catagory_id','name','slug','description','price','stock','image_path','is_active'];
    protected function casts(): array{ return ['price'=>'decimal:2','is_active'=>'boolean'];}
    public function owner(): BelongsTo{return $this->belongsTo(User::class,'user_id');}
    public function catagory():BelongsTo{return $this->belongsTo(Category::class);}
}
