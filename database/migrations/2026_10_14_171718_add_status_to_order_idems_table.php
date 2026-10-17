<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_idems', function (Blueprint $table) {
            $table->string('status')->default('pending')->after('subtotal');
        });

        DB::statement("
            UPDATE order_idems
            SET status = (
                SELECT orders.status FROM orders WHERE orders.id = order_idems.order_id
            )
        ");
    }

    public function down(): void
    {
        Schema::table('order_idems', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
