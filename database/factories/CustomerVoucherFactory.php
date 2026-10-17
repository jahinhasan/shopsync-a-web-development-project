<?php

namespace Database\Factories;

use App\Models\CustomerVoucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CustomerVoucher>
 */
class CustomerVoucherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'SS'.strtoupper(Str::random(10)),
            'source' => 'surprise',
            'status' => 'available',
            'expires_at' => now()->addDays(30),
        ];
    }
}
