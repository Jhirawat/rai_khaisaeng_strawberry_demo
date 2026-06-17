<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('settings')) return;
        $settings = [
            'shop_brand' => '#C35B53',
            'shop_brand_dark' => '#9E4244',
            'shop_soft' => '#E8A7A1',
            'shop_background' => '#FDFBF7',
            'shop_card' => '#FFFFFF',
            'shop_text' => '#4A3E3D',
            'shop_footer' => '#1F4F38',
            'shop_footer_dark' => '#183F2D',
            'shop_cream' => '#E8A7A1',
            'shop_nav_text' => '#4A3E3D',
        ];
        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(['key'=>$key], ['value'=>$value, 'updated_at'=>now(), 'created_at'=>now()]);
        }
    }
    public function down(): void {}
};
