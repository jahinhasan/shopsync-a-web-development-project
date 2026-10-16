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
        Schema::create('shop_application_media', function (Blueprint $table) {
            $table->id();

  $table->foreignId('shop_application_id')
                ->constrained()
                ->cascadeOnDelete();


      $table->string('category');
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();


            $table->timestamps();




            $table->index(['shop_application_id', 'category']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_application_media');
    }
};
