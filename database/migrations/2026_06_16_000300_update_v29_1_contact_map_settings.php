<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $updates = [
            'company_phone_3_name' => 'คุณตี๋',
            'company_phone_3' => '089-265-5685',
            'company_map_url' => 'https://maps.app.goo.gl/GyHtBcHiVML1NudQ9',
        ];
        foreach ($updates as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value, 'updated_at' => now(), 'created_at' => now()]);
        }
    }

    public function down(): void
    {
        // Keep current production contact/map settings unchanged on rollback.
    }
};
