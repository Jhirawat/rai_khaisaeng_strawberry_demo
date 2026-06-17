<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'vat_amount')) $table->decimal('vat_amount', 10, 2)->default(0)->after('shipping_fee');
                if (!Schema::hasColumn('orders', 'before_vat_amount')) $table->decimal('before_vat_amount', 10, 2)->default(0)->after('vat_amount');
                if (!Schema::hasColumn('orders', 'withholding_tax_amount')) $table->decimal('withholding_tax_amount', 10, 2)->default(0)->after('before_vat_amount');
                if (!Schema::hasColumn('orders', 'shipping_rule_note')) $table->string('shipping_rule_note')->nullable()->after('withholding_tax_amount');
            });
        }

        if (Schema::hasTable('settings')) {
            $settings = [
                'shop_brand' => '#D92D3A',
                'shop_brand_dark' => '#9D1F2B',
                'shop_soft' => '#FFF4F4',
                'shop_background' => '#FAFAFA',
                'shop_card' => '#FFFFFF',
                'shop_text' => '#262626',
                'shop_footer' => '#234B36',
                'shop_footer_dark' => '#1D3D2D',
                'shop_cream' => '#E8DDCA',
                'shop_nav_text' => '#333333',
                'vat_rate' => '7',
                'withholding_tax_rate' => '0',
                'receipt_logo' => 'images/receipt-logo.png',
            ];
            foreach ($settings as $key => $value) {
                DB::table('settings')->updateOrInsert(['key'=>$key], ['value'=>$value, 'updated_at'=>now(), 'created_at'=>now()]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                foreach (['shipping_rule_note','withholding_tax_amount','before_vat_amount','vat_amount'] as $column) {
                    if (Schema::hasColumn('orders', $column)) $table->dropColumn($column);
                }
            });
        }
    }
};
