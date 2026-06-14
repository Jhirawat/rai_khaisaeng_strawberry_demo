<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('settings')) return;
        $settings = [
            'company_name_en' => 'Rai Khaisaeng Strawberry',
            'site_logo_th' => 'images/logo-nav-th.png',
            'site_logo_en' => 'images/logo-nav-en.png',
            'receipt_logo' => 'images/logo-th.png',
            'receipt_logo_th' => 'images/logo-th.png',
            'receipt_logo_en' => 'images/logo-en.png',
            'site_favicon' => 'favicon.png',
        ];
        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(['key'=>$key], ['value'=>$value, 'created_at'=>now(), 'updated_at'=>now()]);
        }
    }
    public function down(): void {}
};
