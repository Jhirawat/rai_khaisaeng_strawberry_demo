<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('settings')) return;
        $settings = [
            'company_name' => 'ไร่ไขแสงสตรอเบอร์รี่',
            'company_name_en' => 'Rai Khaisaeng Strawberry',
            'company_subtitle' => 'วิสาหกิจชุมชนแปรรูปสตรอเบอร์รี่',
            'company_address' => '134 หมู่ 4 ตำบลบ่อแก้ว อำเภอสะเมิง จังหวัดเชียงใหม่ 50250',
            'company_address_en' => '134 Moo 4, Bo Kaeo Subdistrict, Samoeng District, Chiang Mai 50250',
            'company_tax_id' => '111xxxxxxxxxx',
            'company_email' => 'info@khaisaeng-strawberry.test',
            'company_phone_1_name' => 'คุณไขแสง',
            'company_phone_1' => '089-999-8295',
            'company_phone_2_name' => 'คุณอ๋อย',
            'company_phone_2' => '081-033-4893',
            'company_phone_3_name' => 'คุณตี๋',
            'company_phone_3' => '089-265-5686',
        ];
        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(['key'=>$key], ['value'=>$value, 'created_at'=>now(), 'updated_at'=>now()]);
        }
    }
    public function down(): void {}
};
