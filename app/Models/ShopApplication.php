<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopApplication extends Model
{
    protected $fillable = [
        'user_id',
        'assigned_admin_id',
        'reviewed_by',
        'shop_name',
        'slug',
        'shop_type',
        'other_shop_type',
        'business_stage',
        'description',
        'business_email',
        'business_phone',
        'country',
        'division',
        'district',
        'upazila',
        'full_address',
        'postal_code',
        'estimated_product_count',
        'product_categories',
        'business_history',
        'previous_platforms',
        'supplier_information',
        'additional_information',
        'legal_name',
        'date_of_birth',
        'identity_document_type',
        'identity_document_number',
        'identity_photo_path',
        'identity_document_path',
        'terms_accepted',
        'terms_accepted_at',
        'status',
        'admin_notes',
        'rejection_reason',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'terms_accepted' => 'boolean',
            'terms_accepted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }





    public function media(): HasMany
    {
        return $this->hasMany(ShopApplicationMedia::class);
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(ShopApplicationReview::class)
            ->oldest();
    }
}
