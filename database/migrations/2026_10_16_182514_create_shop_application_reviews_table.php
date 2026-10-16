<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_application_reviews', function (Blueprint $table) {
            $table->id();
             $table->foreignId('shop_application_id')
                ->constrained('shop_applications')
                ->cascadeOnDelete();

            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('actor_role', 30);
            $table->string('action', 50);
            $table->text('comment')->nullable();

            $table->timestamps();

            $table->index([
                'shop_application_id',
                'created_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_application_reviews');
    }
};
