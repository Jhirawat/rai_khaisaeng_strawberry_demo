<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_payment','confirmed','paid','preparing','packed','shipped','delivered','delivery_failed','cancelled') NOT NULL DEFAULT 'pending_payment'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('orders')
            ->where('status', 'confirmed')
            ->update(['status' => 'pending_payment']);
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_payment','paid','preparing','packed','shipped','delivered','delivery_failed','cancelled') NOT NULL DEFAULT 'pending_payment'");
    }
};
