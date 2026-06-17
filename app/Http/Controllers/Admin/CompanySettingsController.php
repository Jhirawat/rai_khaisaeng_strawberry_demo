<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Support\ActivityLogger;

class CompanySettingsController extends Controller
{
    private array $fields = [
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
        'company_phone_3' => '089-265-5685',
        'company_map_url' => 'https://maps.app.goo.gl/GyHtBcHiVML1NudQ9',
        'site_logo_th' => 'images/logo-nav-th.png',
        'site_logo_en' => 'images/logo-nav-en.png',
        'receipt_logo_th' => 'images/logo-th.png',
        'receipt_logo_en' => 'images/logo-en.png',
        'site_favicon' => 'favicon.png',
        'social_facebook_url' => '#',
        'social_youtube_url' => '#',
        'social_line_url' => '#',
        'home_hero_image' => 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?auto=format&fit=crop&w=1600&q=80',
    ];

    public function edit()
    {
        $settings = [];
        foreach ($this->fields as $key => $default) {
            $settings[$key] = Setting::getValue($key, $default);
        }
        return view('admin.settings.company', compact('settings'));
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach ($this->fields as $key => $default) $rules[$key] = ['nullable','string','max:5000'];
        foreach (['site_logo_th_file','site_logo_en_file','receipt_logo_th_file','receipt_logo_en_file','site_favicon_file','home_hero_image_file'] as $fileField) {
            $rules[$fileField] = ['nullable','file','mimes:png,jpg,jpeg,webp,ico','max:2048'];
        }
        $rules['company_email'] = ['nullable','email','max:255'];
        $data = $request->validate($rules);
        foreach ($this->fields as $key => $default) {
            if (!str_ends_with($key, '_file')) {
                Setting::setValue($key, $data[$key] ?? Setting::getValue($key, $default));
            }
        }

        $fileMap = [
            'site_logo_th_file' => 'site_logo_th',
            'site_logo_en_file' => 'site_logo_en',
            'receipt_logo_th_file' => 'receipt_logo_th',
            'receipt_logo_en_file' => 'receipt_logo_en',
            'site_favicon_file' => 'site_favicon',
            'home_hero_image_file' => 'home_hero_image',
        ];
        foreach ($fileMap as $input => $settingKey) {
            if ($request->hasFile($input)) {
                $path = $request->file($input)->store('settings', 'public');
                Setting::setValue($settingKey, 'storage/'.$path);
            }
        }
        ActivityLogger::log('settings.company_updated', null, ['updated_fields'=>array_keys($data)]);
        return back()->with('success', 'บันทึกข้อมูลร้านค้าและโลโก้เรียบร้อยแล้ว');
    }
}
