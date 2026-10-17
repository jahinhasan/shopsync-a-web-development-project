<?php

namespace Database\Factories;

use App\Models\ShopApplication;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ShopApplication>
 */
class ShopApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $shopName = fake()->unique()->company();

        return [
            'shop_name' => $shopName,
            'slug' => Str::slug($shopName),
            'shop_type' => 'general',
            'business_stage' => 'running',
            'description' => fake()->paragraph(),
            'business_phone' => fake()->phoneNumber(),
            'division' => fake()->city(),
            'district' => fake()->city(),
            'full_address' => fake()->address(),
            'product_categories' => 'general',
            'legal_name' => fake()->name(),
            'identity_document_type' => 'nid',
            'identity_photo_path' => 'shop-applications/'.Str::random(12).'.jpg',
            'status' => 'pending',
        ];
    }

    /**
     * An application approved by the admin with an active shop.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'shop_status' => 'active',
        ]);
    }
}
