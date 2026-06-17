<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders','cancelled_at')) $table->timestamp('cancelled_at')->nullable()->after('status');
            if (! Schema::hasColumn('orders','stock_returned_at')) $table->timestamp('stock_returned_at')->nullable()->after('cancelled_at');
        });
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments','verified_by')) $table->foreignId('verified_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            if (! Schema::hasColumn('payments','verified_at')) $table->timestamp('verified_at')->nullable()->after('verified_by');
        });
    }
    public function down(): void {}
};
