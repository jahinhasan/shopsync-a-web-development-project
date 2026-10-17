<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_conversations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('shop_owner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->string('product_name');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['customer_id', 'shop_owner_id', 'product_id'],
                'shop_conversations_participants_product_unique'
            );

            $table->index(['customer_id', 'last_message_at']);
            $table->index(['shop_owner_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_conversations');
    }
};