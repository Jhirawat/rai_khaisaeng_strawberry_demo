<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('settings')) return;

        $settings = [
            'admin_sidebar_start' => '#2461F0',
            'admin_sidebar_end' => '#EFA9F4',
            'admin_accent' => '#D94B5B',
            'admin_background' => '#F5F7F6',
            'admin_card' => '#FFFFFF',
            'admin_text' => '#13231F',
            'site_favicon' => 'favicon.png',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('settings')) return;
        DB::table('settings')->where('key', 'site_favicon')->delete();
    }
};
