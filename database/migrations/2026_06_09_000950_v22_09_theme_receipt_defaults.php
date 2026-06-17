<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('settings')) return;
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
        ];
        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(['key'=>$key], ['value'=>$value, 'updated_at'=>now(), 'created_at'=>now()]);
        }
    }
    public function down(): void {}
};
