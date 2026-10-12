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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
             $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('category_id')->constrained()->restrictOnDelete();
        $table->string('name', 180);
        $table->string('slug')->unique();
        $table->text('description')->nullable();
        $table->decimal('price', 10, 2);
        $table->unsignedInteger('stock')->default(0);
        $table->string('image_path')->nullable();
        $table->boolean('is_active')->default(true);
            $table->timestamps();

                    $table->index(['category_id', 'is_active']);
        $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
