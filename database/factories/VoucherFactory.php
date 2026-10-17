<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Voucher>
 */
class VoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'ShopSync Voucher',
            'discount_type' => 'percent',
            'discount_value' => 5,
            'min_order_total' => 500,
            'max_discount' => 100,
            'is_active' => true,
            'funding_source' => 'platform',
            'expires_in_days' => 30,
        ];
    }

    /**
     * A voucher scoped to a single category.
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => $category->id,
        ]);
    }
}
