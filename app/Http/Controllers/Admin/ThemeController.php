<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Support\ActivityLogger;

class ThemeController extends Controller
{
    private array $defaults = [
        // Admin / Super Admin theme
        'admin_sidebar_start' => '#2461F0',
        'admin_sidebar_end' => '#EFA9F4',
        'admin_accent' => '#D94B5B',
        'admin_background' => '#F5F7F6',
        'admin_card' => '#FFFFFF',
        'admin_text' => '#13231F',

        // Guest / Member storefront theme default: Strawberry & Cream
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

    private array $presets = [
        'strawberry_cream' => [
            'name'=>'Strawberry & Cream',
            'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B','admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F','shop_brand'=>'#C35B53','shop_brand_dark'=>'#9E4244','shop_soft'=>'#E8A7A1','shop_background'=>'#FDFBF7','shop_card'=>'#FFFFFF','shop_text'=>'#4A3E3D','shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#E8A7A1','shop_nav_text'=>'#4A3E3D',
        ],
        'earthy_strawberry' => [
            'name'=>'Earthy Strawberry',
            'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B','admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F','shop_brand'=>'#7A8B7B','shop_brand_dark'=>'#5F6F61','shop_soft'=>'#D28A81','shop_background'=>'#F4F1EA','shop_card'=>'#FFFFFF','shop_text'=>'#3E3A39','shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#D28A81','shop_nav_text'=>'#3E3A39',
        ],
        'minimalist_berry' => [
            'name'=>'Minimalist Berry',
            'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B','admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F','shop_brand'=>'#9E4244','shop_brand_dark'=>'#753033','shop_soft'=>'#F5E6E4','shop_background'=>'#FFFFFF','shop_card'=>'#FFFFFF','shop_text'=>'#2B2B2B','shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#F5E6E4','shop_nav_text'=>'#2B2B2B',
        ],
        'strawberry_matcha' => [
            'name'=>'Strawberry Matcha',
            'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B','admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F','shop_brand'=>'#DB9390','shop_brand_dark'=>'#B76F6C','shop_soft'=>'#C8D3C5','shop_background'=>'#FAF6F0','shop_card'=>'#FFFFFF','shop_text'=>'#3A4238','shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#C8D3C5','shop_nav_text'=>'#3A4238',
        ],
        'smoked_strawberry' => [
            'name'=>'Smoked Strawberry',
            'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B','admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F','shop_brand'=>'#69757A','shop_brand_dark'=>'#535E63','shop_soft'=>'#DCAE9E','shop_background'=>'#F2EFF0','shop_card'=>'#FFFFFF','shop_text'=>'#2D3134','shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#DCAE9E','shop_nav_text'=>'#2D3134',
        ],
        'vintage_berry_shaved_ice' => [
            'name'=>'Vintage Berry Shaved Ice',
            'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B','admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F','shop_brand'=>'#4A584E','shop_brand_dark'=>'#37423A','shop_soft'=>'#E7CDCC','shop_background'=>'#F7F7F5','shop_card'=>'#FFFFFF','shop_text'=>'#2F3631','shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#E7CDCC','shop_nav_text'=>'#2F3631',
        ],
        'strawberry_jam_toast' => [
            'name'=>'Strawberry Jam & Toast',
            'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B','admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F','shop_brand'=>'#B35A55','shop_brand_dark'=>'#8E4642','shop_soft'=>'#E4C7B7','shop_background'=>'#FAF4EE','shop_card'=>'#FFFFFF','shop_text'=>'#42322A','shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#E4C7B7','shop_nav_text'=>'#42322A',
        ],
        'vintage_berry_shaved_ice_alt' => [
            'name'=>'Vintage Berry Shaved Ice 2',
            'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B','admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F','shop_brand'=>'#4A584E','shop_brand_dark'=>'#37423A','shop_soft'=>'#E7CDCC','shop_background'=>'#F7F7F5','shop_card'=>'#FFFFFF','shop_text'=>'#2F3631','shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#E7CDCC','shop_nav_text'=>'#2F3631',
        ],
        'sweet_playful' => [
            'name'=>'Sweet & Playful',
            'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B','admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F','shop_brand'=>'#FFB7B2','shop_brand_dark'=>'#E9908B','shop_soft'=>'#FFDAC1','shop_background'=>'#FFF7F2','shop_card'=>'#FFFFFF','shop_text'=>'#3E3A39','shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#FFDAC1','shop_nav_text'=>'#3E3A39',
        ],
        'luxury_premium' => [
            'name'=>'Luxury & Premium',
            'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B','admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F','shop_brand'=>'#5C0612','shop_brand_dark'=>'#3F040C','shop_soft'=>'#F8F1E3','shop_background'=>'#FFFDF8','shop_card'=>'#FFFFFF','shop_text'=>'#1E1717','shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#D4AF37','shop_nav_text'=>'#1E1717',
        ],
        'retro_vintage' => [
            'name'=>'Retro & Vintage',
            'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B','admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F','shop_brand'=>'#C84B31','shop_brand_dark'=>'#9D3A25','shop_soft'=>'#E4A951','shop_background'=>'#FFF8EB','shop_card'=>'#FFFFFF','shop_text'=>'#3B2B20','shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#E4A951','shop_nav_text'=>'#3B2B20',
        ],
        'bold_vibrant' => [
            'name'=>'Bold & Vibrant',
            'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B','admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F','shop_brand'=>'#FF2E63','shop_brand_dark'=>'#D91D4E','shop_soft'=>'#FFE2EE','shop_background'=>'#FFFFFF','shop_card'=>'#FFFFFF','shop_text'=>'#0F2C59','shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#FFE2EE','shop_nav_text'=>'#0F2C59',
        ],
    ];

    public function edit()
    {
        $theme = $this->currentTheme();
        return view('admin.theme.edit', ['theme'=>$theme, 'presets'=>$this->presets]);
    }

    public function update(Request $request)
    {
        $rules = [];
        foreach ($this->defaults as $key => $value) {
            $rules[$key] = ['required','regex:/^#[0-9A-Fa-f]{6}$/'];
        }

        $labels = [
            'admin_sidebar_start' => 'สี Sidebar ด้านบน',
            'admin_sidebar_end' => 'สี Sidebar ด้านล่าง',
            'admin_accent' => 'สีปุ่มหลักหลังบ้าน',
            'admin_background' => 'สีพื้นหลังหลังบ้าน',
            'admin_card' => 'สีการ์ดหลังบ้าน',
            'admin_text' => 'สีตัวอักษรหลังบ้าน',
            'shop_brand' => 'สีหลักหน้าร้าน',
            'shop_brand_dark' => 'สีหลักเข้มหน้าร้าน',
            'shop_soft' => 'สีพื้นอ่อนหน้าร้าน',
            'shop_background' => 'สีพื้นหลังหน้าร้าน',
            'shop_card' => 'สีการ์ดหน้าร้าน',
            'shop_text' => 'สีตัวอักษรหน้าร้าน',
            'shop_footer' => 'สี Footer หน้าร้าน',
            'shop_footer_dark' => 'สี Footer เข้มหน้าร้าน',
            'shop_cream' => 'สีแถบข้อมูลหน้าร้าน',
            'shop_nav_text' => 'สีตัวอักษร Navbar/โลโก้หน้าร้าน',
        ];

        $data = $request->validate($rules, [], $labels);
        foreach ($data as $key => $value) {
            Setting::setValue($key, strtoupper($value));
        }
        ActivityLogger::log('settings.theme_updated', null, ['updated_fields'=>array_keys($data)]);
        return back()->with('success', 'บันทึกธีมทั้งฝั่งหลังบ้านและหน้าร้านเรียบร้อยแล้ว');
    }

    public function preset(string $preset)
    {
        abort_unless(isset($this->presets[$preset]), 404);
        foreach ($this->presets[$preset] as $key => $value) {
            if ($key !== 'name') Setting::setValue($key, strtoupper($value));
        }
        ActivityLogger::log('settings.theme_preset', null, ['preset'=>$preset, 'name'=>$this->presets[$preset]['name']]);
        return back()->with('success', 'เปลี่ยนธีมเป็น '.$this->presets[$preset]['name'].' แล้ว');
    }

    public function reset()
    {
        foreach ($this->defaults as $key => $value) {
            Setting::setValue($key, $value);
        }
        ActivityLogger::log('settings.theme_reset', null, []);
        return back()->with('success', 'รีเซ็ตธีมเป็นค่าเริ่มต้นแล้ว');
    }

    private function currentTheme(): array
    {
        $theme = [];
        foreach ($this->defaults as $key => $default) {
            $theme[$key] = Setting::getValue($key, $default);
        }
        return $theme;
    }
}
