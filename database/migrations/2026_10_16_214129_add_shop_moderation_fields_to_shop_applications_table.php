<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_applications', function (Blueprint $table) {
            $table->string('shop_status', 20)
                ->default('active')
                ->index();

            $table->text('shop_status_reason')->nullable();

            $table->foreignId('shop_status_updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('shop_status_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shop_applications', function (Blueprint $table) {
            $table->dropForeign(['shop_status_updated_by']);
            $table->dropIndex(['shop_status']);
            $table->dropColumn([
                'shop_status',
                'shop_status_reason',
                'shop_status_updated_by',
                'shop_status_updated_at',
            ]);
        });
    }
};