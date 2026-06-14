<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void {
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_payment','paid','preparing','packed','shipped','delivered','delivery_failed','cancelled') NOT NULL DEFAULT 'pending_payment'");
    }
    public function down(): void {
        DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_payment','paid','preparing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending_payment'");
    }
};
