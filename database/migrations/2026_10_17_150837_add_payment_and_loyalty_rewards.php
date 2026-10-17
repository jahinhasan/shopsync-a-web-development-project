<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('reward_points')->default(0);
            $table->boolean('surprise_voucher_enabled')->default(false);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_reference', 120)->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
            $table->string('voucher_code', 40)->nullable();
            $table->decimal('voucher_discount_total', 10, 2)->default(0);
        });

        Schema::table('order_idems', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('pending');
            $table->foreignId('payment_verified_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('payment_verified_at')->nullable();

            $table->unsignedInteger('reward_points_per_unit')->default(0);
            $table->boolean('surprise_voucher_eligible')->default(false);
            $table->foreignId('reward_category_id')
                ->nullable()->constrained('categories')->nullOnDelete();
            $table->timestamp('rewards_processed_at')->nullable();
            $table->timestamp('surprise_voucher_issued_at')->nullable();

            $table->decimal('voucher_discount_amount', 10, 2)->default(0);
            $table->index(['order_id', 'payment_status']);
        });

        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('discount_type', 20)->default('percent');
            $table->decimal('discount_value', 10, 2);
            $table->decimal('min_order_total', 10, 2)->default(500);
            $table->decimal('max_discount', 10, 2)->default(100);
            $table->unsignedInteger('points_cost')->nullable();

            $table->foreignId('product_id')
                ->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('category_id')
                ->nullable()->constrained('categories')->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->string('funding_source', 20)->default('platform');
            $table->unsignedSmallInteger('expires_in_days')->default(30);
            $table->timestamps();
        });

        Schema::create('customer_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voucher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40)->unique();
            $table->string('source', 20); // surprise / points
            $table->foreignId('source_order_item_id')
                ->nullable()->constrained('order_idems')->nullOnDelete()->unique();

            $table->string('status', 20)->default('available');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('redeemed_order_id')
                ->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'expires_at']);
        });

        Schema::create('customer_point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')
                ->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')
                ->nullable()->constrained('order_idems')->nullOnDelete();
            $table->foreignId('customer_voucher_id')
                ->nullable()->constrained('customer_vouchers')->nullOnDelete();

            $table->string('type', 20); // earned / spent / reversed
            $table->unsignedInteger('points');
            $table->timestamps();

            $table->unique(['order_item_id', 'type']);
            $table->unique(['customer_voucher_id', 'type']);
            $table->index(['user_id', 'created_at']);
        });

        // Assign bounded rewards to existing products too.
        DB::table('products')->orderBy('id')->chunkById(300, function ($products) {
            foreach ($products as $product) {
                $price = (float) $product->price;

                [$min, $max] = match (true) {
                    $price < 100 => [1, 3],
                    $price < 500 => [3, 8],
                    $price < 1500 => [8, 15],
                    $price < 5000 => [15, 30],
                    default => [30, 50],
                };

                DB::table('products')->where('id', $product->id)->update([
                    'reward_points' => random_int($min, $max),
                    'surprise_voucher_enabled' => random_int(1, 100) <= 12,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_point_transactions');
        Schema::dropIfExists('customer_vouchers');
        Schema::dropIfExists('vouchers');

        Schema::table('order_idems', function (Blueprint $table) {
            $table->dropForeign(['payment_verified_by']);
            $table->dropForeign(['reward_category_id']);
            $table->dropIndex(['order_id', 'payment_status']);
            $table->dropColumn([
                'payment_status',
                'payment_verified_by',
                'payment_verified_at',
                'reward_points_per_unit',
                'surprise_voucher_eligible',
                'reward_category_id',
                'rewards_processed_at',
                'surprise_voucher_issued_at',
                'voucher_discount_amount',
            ]);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_reference',
                'payment_submitted_at',
                'voucher_code',
                'voucher_discount_total',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'reward_points',
                'surprise_voucher_enabled',
            ]);
        });
    }
};