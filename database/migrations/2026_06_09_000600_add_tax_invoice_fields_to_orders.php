<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'needs_tax_invoice')) {
                $table->boolean('needs_tax_invoice')->default(false)->after('shipping_address_snapshot');
            }
            if (!Schema::hasColumn('orders', 'customer_tax_id')) {
                $table->string('customer_tax_id')->nullable()->after('needs_tax_invoice');
            }
            if (!Schema::hasColumn('orders', 'customer_tax_name')) {
                $table->string('customer_tax_name')->nullable()->after('customer_tax_id');
            }
            if (!Schema::hasColumn('orders', 'customer_tax_address')) {
                $table->text('customer_tax_address')->nullable()->after('customer_tax_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['customer_tax_address','customer_tax_name','customer_tax_id','needs_tax_invoice'] as $column) {
                if (Schema::hasColumn('orders', $column)) $table->dropColumn($column);
            }
        });
    }
};
