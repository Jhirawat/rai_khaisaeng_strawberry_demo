<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\{User,Category,Product,Inventory,ProductImage,Promotion};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // บัญชีทดสอบเดิม
        User::updateOrCreate(['email'=>'admin@maeyangha.test'],['name'=>'Super Admin','password'=>Hash::make('password'),'role'=>'super_admin','phone'=>'0899998295','is_active'=>true]);
        User::updateOrCreate(['email'=>'member@maeyangha.test'],['name'=>'Demo Member','password'=>Hash::make('password'),'role'=>'member','phone'=>'0810334893','is_active'=>true]);

        // บัญชีทดสอบหลักสำหรับส่งอาจารย์ / Tester
        User::updateOrCreate(['email'=>'user_test@khaisaeng.test'],['name'=>'user_test','password'=>Hash::make('password'),'role'=>'member','phone'=>'0812345678','is_active'=>true]);
        User::updateOrCreate(['email'=>'admin_test@khaisaeng.test'],['name'=>'admin_test','password'=>Hash::make('password'),'role'=>'admin','phone'=>'0899998295','is_active'=>true]);
        User::updateOrCreate(['email'=>'sbadmin_test@khaisaeng.test'],['name'=>'sb admin_test','password'=>Hash::make('password'),'role'=>'super_admin','phone'=>'0892655686','is_active'=>true]);

        $this->call(ThaiAddressFullSeeder::class);

        // v28 Seed Protection: บน Production จะไม่รัน DemoProductionSeeder อัตโนมัติ เพื่อกันข้อมูลจริงโดนทับ/เพิ่มซ้ำ
        if (! app()->environment('production') || filter_var(env('ALLOW_DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->call(DemoProductionSeeder::class);
        }

        Promotion::updateOrCreate(
            ['position'=>'home_banner','sort_order'=>1],
            ['title'=>'โปรโมชันสินค้าแปรรูปสตรอว์เบอร์รี่ตามฤดูกาล','subtitle'=>'Promotion / Advertising Area','description'=>'แก้ไขแบนเนอร์นี้ได้จากเมนู Admin > โฆษณา / โปรโมชั่น','button_text'=>'ดูสินค้าโปรโมชั่น','button_url'=>'/products','is_active'=>true]
        );
    }

    private function seedProducts(): void
    {
        $cats = [
            ['th'=>'สตรอว์เบอร์รีสด','en'=>'Fresh Strawberry','slug'=>'strawberry-fresh','prefix'=>'ST-FRESH'],
            ['th'=>'สตรอว์เบอร์รีอบแห้ง','en'=>'Dried Strawberry','slug'=>'dried-strawberry','prefix'=>'ST-DRIED'],
            ['th'=>'น้ำสตรอว์เบอร์รี','en'=>'Strawberry Juice','slug'=>'strawberry-juice','prefix'=>'ST-JUICE'],
            ['th'=>'แยมสตรอว์เบอร์รี','en'=>'Strawberry Jam','slug'=>'strawberry-jam','prefix'=>'ST-JAM'],
            ['th'=>'ขนมแปรรูปจากสตรอว์เบอร์รี','en'=>'Strawberry Snacks','slug'=>'strawberry-snacks','prefix'=>'ST-SNACK'],
            ['th'=>'ของฝากและของที่ระลึก','en'=>'Souvenirs','slug'=>'souvenirs','prefix'=>'ST-GIFT'],
        ];
        foreach($cats as $index=>$c){
            $cat=Category::updateOrCreate(
                ['slug'=>$c['slug']],
                ['name'=>$c['th'],'name_en'=>$c['en'],'description'=>'หมวดหมู่ '.$c['th'],'description_en'=>$c['en'].' category']
            );
            for($i=1;$i<=3;$i++){
                $p=Product::firstOrCreate(
                    ['sku'=>$c['prefix'].'-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)],
                    [
                        'category_id'=>$cat->id,
                        'name'=>$c['th'].' รุ่น '.$i,
                        'name_en'=>$c['en'].' Model '.$i,
                        'slug'=>$c['slug'].'-'.$i,
                        'description'=>'สินค้าแปรรูปจากวิสาหกิจชุมชนไร่ไขแสงสตรอเบอร์รี่ ใช้เป็นข้อมูลทดสอบระบบ สามารถเปลี่ยนรูปและรายละเอียดเป็นข้อมูลจริงได้ภายหลัง',
                        'description_en'=>'Test product from Rai Khaisaeng Strawberry Farm. You can replace this information and image with real product data later.',
                        'price'=>rand(79,399),
                        'cost'=>rand(30,120),
                        'status'=>'active',
                        'featured'=>$i==1,
                        'added_at'=>now()->subDays(rand(1,60))
                    ]
                );
                $p->update(['name_en'=>$p->name_en ?: $c['en'].' Model '.$i, 'description_en'=>$p->description_en ?: 'Test product from Rai Khaisaeng Strawberry Farm.']);
                Inventory::firstOrCreate(['product_id'=>$p->id],['quantity'=>rand(5,80),'low_stock_threshold'=>10]);
                ProductImage::firstOrCreate(['product_id'=>$p->id,'is_primary'=>true],['path'=>'images/products/product-'.($index+1).'.svg']);
            }
        }
    }


    private function categorySlug(string $name): string
    {
        return [
            'สตรอว์เบอร์รีสด' => 'strawberry-fresh',
            'สตรอว์เบอร์รีอบแห้ง' => 'dried-strawberry',
            'น้ำสตรอว์เบอร์รี' => 'strawberry-juice',
            'แยมสตรอว์เบอร์รี' => 'strawberry-jam',
            'ขนมแปรรูปจากสตรอว์เบอร์รี' => 'strawberry-snacks',
            'ของฝากและของที่ระลึก' => 'souvenirs',
        ][$name] ?? 'category-'.strtolower(substr(md5($name),0,6));
    }

    private function seedThaiAddress(): void
    {
        // โครงสร้างตารางรองรับการนำเข้าไฟล์เต็มจาก codesanook/thailand-administrative-division...
        // ข้อมูลด้านล่างเป็นชุดตัวอย่างพร้อมใช้สำหรับทดสอบ cascading dropdown ในระบบ
        $data = [
            'เชียงใหม่' => [
                'สะเมิง' => ['บ่อแก้ว'=>'50250','สะเมิงใต้'=>'50250','สะเมิงเหนือ'=>'50250','แม่สาบ'=>'50250','ยั้งเมิน'=>'50250'],
                'เมืองเชียงใหม่' => ['ศรีภูมิ'=>'50200','สุเทพ'=>'50200','ช้างคลาน'=>'50100','พระสิงห์'=>'50200'],
                'หางดง' => ['หางดง'=>'50230','หนองควาย'=>'50230','สันผักหวาน'=>'50230'],
            ],
            'กรุงเทพมหานคร' => [
                'เขตบางกอกน้อย' => ['อรุณอมรินทร์'=>'10700','ศิริราช'=>'10700'],
                'เขตพระนคร' => ['พระบรมมหาราชวัง'=>'10200','วังบูรพาภิรมย์'=>'10200'],
                'เขตปทุมวัน' => ['ปทุมวัน'=>'10330','ลุมพินี'=>'10330'],
            ],
            'เชียงราย' => [
                'เมืองเชียงราย' => ['เวียง'=>'57000','รอบเวียง'=>'57000'],
                'แม่สาย' => ['แม่สาย'=>'57130','เวียงพางคำ'=>'57130'],
            ],
            'ลำพูน' => [
                'เมืองลำพูน' => ['ในเมือง'=>'51000','เหมืองง่า'=>'51000'],
                'แม่ทา' => ['ทาสบเส้า'=>'51140','ทากาศ'=>'51170'],
            ],
        ];

        $allProvinces = ['กรุงเทพมหานคร','กระบี่','กาญจนบุรี','กาฬสินธุ์','กำแพงเพชร','ขอนแก่น','จันทบุรี','ฉะเชิงเทรา','ชลบุรี','ชัยนาท','ชัยภูมิ','ชุมพร','เชียงราย','เชียงใหม่','ตรัง','ตราด','ตาก','นครนายก','นครปฐม','นครพนม','นครราชสีมา','นครศรีธรรมราช','นครสวรรค์','นนทบุรี','นราธิวาส','น่าน','บึงกาฬ','บุรีรัมย์','ปทุมธานี','ประจวบคีรีขันธ์','ปราจีนบุรี','ปัตตานี','พระนครศรีอยุธยา','พะเยา','พังงา','พัทลุง','พิจิตร','พิษณุโลก','เพชรบุรี','เพชรบูรณ์','แพร่','ภูเก็ต','มหาสารคาม','มุกดาหาร','แม่ฮ่องสอน','ยโสธร','ยะลา','ร้อยเอ็ด','ระนอง','ระยอง','ราชบุรี','ลพบุรี','ลำปาง','ลำพูน','เลย','ศรีสะเกษ','สกลนคร','สงขลา','สตูล','สมุทรปราการ','สมุทรสงคราม','สมุทรสาคร','สระแก้ว','สระบุรี','สิงห์บุรี','สุโขทัย','สุพรรณบุรี','สุราษฎร์ธานี','สุรินทร์','หนองคาย','หนองบัวลำภู','อ่างทอง','อำนาจเจริญ','อุดรธานี','อุตรดิตถ์','อุทัยธานี','อุบลราชธานี'];
        foreach ($allProvinces as $provinceName) {
            $province = ThaiProvince::firstOrCreate(['name_th'=>$provinceName], ['code'=>null]);
            if (!array_key_exists($provinceName, $data) && !$province->districts()->exists()) {
                $district = ThaiDistrict::firstOrCreate(['province_id'=>$province->id,'name_th'=>'เมือง'.$provinceName], ['code'=>null]);
                ThaiSubdistrict::firstOrCreate(['district_id'=>$district->id,'name_th'=>'ในเมือง'], ['zip_code'=>'00000']);
            }
        }

        foreach($data as $provinceName=>$districts){
            $province = ThaiProvince::firstOrCreate(['name_th'=>$provinceName],['code'=>null]);
            foreach($districts as $districtName=>$subs){
                $district = ThaiDistrict::firstOrCreate(['province_id'=>$province->id,'name_th'=>$districtName],['code'=>null]);
                foreach($subs as $subName=>$zip){
                    ThaiSubdistrict::firstOrCreate(['district_id'=>$district->id,'name_th'=>$subName],['zip_code'=>$zip]);
                }
            }
        }
    }
}
