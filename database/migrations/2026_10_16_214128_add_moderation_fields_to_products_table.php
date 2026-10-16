<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('admin_status', 20)
                ->default('active')
                ->index();

            $table->text('admin_reason')->nullable();

            $table->foreignId('moderated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['moderated_by']);
            $table->dropIndex(['admin_status']);
            $table->dropColumn([
                'admin_status',
                'admin_reason',
                'moderated_by',
                'deleted_at',
            ]);
        });
    }
};