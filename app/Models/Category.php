<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id','name','slug','description','is_active'];

    public function parent(): BelongsTo{return $this->belongsTo(Category::class, 'parent_id'); }

    public function children(): HasMany{ return $this->hasMany(self::class, 'parent_id');}
    public function products(): HasMany{return $this->hasMany(Product::class);}

}
