<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DemoProductionSeeder extends Seeder
{
    private array $categories = [
        ['name'=>'สตรอว์เบอร์รี่สด','name_en'=>'Fresh Strawberry','slug'=>'fresh-strawberry','prefix'=>'ST-FRESH','base'=>239],
        ['name'=>'สตรอว์เบอร์รี่อบแห้ง','name_en'=>'Dried Strawberry','slug'=>'dried-strawberry','prefix'=>'ST-DRIED','base'=>186],
        ['name'=>'น้ำสตรอว์เบอร์รี่','name_en'=>'Strawberry Juice','slug'=>'strawberry-juice','prefix'=>'ST-JUICE','base'=>149],
        ['name'=>'แยมสตรอว์เบอร์รี่','name_en'=>'Strawberry Jam','slug'=>'strawberry-jam','prefix'=>'ST-JAM','base'=>123],
        ['name'=>'ขนมแปรรูปจากสตรอว์เบอร์รี่','name_en'=>'Strawberry Snacks','slug'=>'strawberry-snacks','prefix'=>'ST-SNACK','base'=>160],
        ['name'=>'ของฝากและของที่ระลึก','name_en'=>'Souvenirs & Gifts','slug'=>'souvenirs-gifts','prefix'=>'ST-GIFT','base'=>199],
        ['name'=>'สินค้าออร์แกนิก','name_en'=>'Organic Products','slug'=>'organic-products','prefix'=>'ST-ORG','base'=>259],
        ['name'=>'เซ็ตของขวัญ','name_en'=>'Gift Sets','slug'=>'gift-sets','prefix'=>'ST-SET','base'=>399],
    ];

    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (['notifications','shipments','payments','order_items','orders','reviews','inventory_logs','inventories','product_images','products','categories','cart_items','carts','shipping_addresses','users'] as $table) {
            DB::table($table)->truncate();
        }
        Schema::enableForeignKeyConstraints();

        $now = now();
        $this->seedUsers($now);
        $this->seedCategoriesAndProducts($now);
        $this->seedOrdersPaymentsShipmentsAndReviews($now);
        $this->seedNotifications($now);
    }

    private function seedUsers($now): void
    {
        $users = [
            ['name'=>'Super Admin','email'=>'sbadmin_test@khaisaeng.test','password'=>Hash::make('password'),'phone'=>'0892655686','role'=>'super_admin','is_active'=>true,'address'=>'134 หมู่ 4 ตำบลบ่อแก้ว อำเภอสะเมิง จังหวัดเชียงใหม่ 50250','email_verified_at'=>$now,'created_at'=>$now->copy()->subMonths(15),'updated_at'=>$now],
            ['name'=>'Admin Test','email'=>'admin_test@khaisaeng.test','password'=>Hash::make('password'),'phone'=>'0899998295','role'=>'admin','is_active'=>true,'address'=>'ไร่ไขแสงสตรอเบอร์รี่ อำเภอสะเมิง จังหวัดเชียงใหม่','email_verified_at'=>$now,'created_at'=>$now->copy()->subMonths(14),'updated_at'=>$now],
            ['name'=>'Staff Demo','email'=>'staff_test@khaisaeng.test','password'=>Hash::make('password'),'phone'=>'0810334893','role'=>'staff','is_active'=>true,'address'=>'เชียงใหม่','email_verified_at'=>$now,'created_at'=>$now->copy()->subMonths(13),'updated_at'=>$now],
            ['name'=>'User Test','email'=>'user_test@khaisaeng.test','password'=>Hash::make('password'),'phone'=>'0812345678','role'=>'member','is_active'=>true,'address'=>'เชียงใหม่','email_verified_at'=>$now,'created_at'=>$now->copy()->subMonths(12),'updated_at'=>$now],
        ];
        $thaiNames = ['กานต์','ณัฐ','มินตรา','ภัทร','ศิริ','อรพิน','เมธา','ชลธิชา','วรัญญา','ธนกร','ปวีณา','กิตติ','อัญชลี','รัตนา','นภัส','เจษฎา','พิมพ์ชนก','สุภาวดี','ชญานิน','ธนวัฒน์','ปุณณภา','กรกมล','ภาณุ','วริศรา','พิชชา','นที','อนงค์','สิริกร','พิชญ์','อรทัย','ธีรภัทร์','ชนาธิป','เบญจพร','สุเมธ','ขวัญชนก','ธัญญา','ภูริ','อริสา','จิรพัฒน์','ปิยธิดา','อาทิตย์','แพรวา','นรินทร์','นลิน','กมลชนก','วุฒิชัย','ปาริชาติ','ศุภชัย','น้ำฝน','เอกชัย'];
        $provinces = ['เชียงใหม่','เชียงราย','ลำพูน','กรุงเทพมหานคร','ชลบุรี','นนทบุรี','ขอนแก่น','ภูเก็ต','นครราชสีมา','สงขลา'];
        for ($i=1; $i<=60; $i++) {
            $name = $thaiNames[($i-1) % count($thaiNames)].' ลูกค้าทดสอบ '.$i;
            $created = $now->copy()->subDays(365 - (($i*7) % 365));
            $users[] = [
                'name'=>$name,
                'email'=>'member'.str_pad((string)$i,3,'0',STR_PAD_LEFT).'@khaisaeng.test',
                'password'=>Hash::make('password'),
                'phone'=>'08'.str_pad((string)(10000000+$i*17321),8,'0',STR_PAD_LEFT),
                'role'=>'member','is_active'=>$i % 17 !== 0,
                'address'=>'บ้านเลขที่ '.($i+10).' หมู่ '.(($i%9)+1).' ตำบลบ่อแก้ว อำเภอสะเมิง จังหวัด'.$provinces[$i % count($provinces)].' 50250',
                'email_verified_at'=>$created->copy()->addHours(3),
                'created_at'=>$created,'updated_at'=>$created->copy()->addDays($i%20),
            ];
        }
        DB::table('users')->insert($users);

        $members = DB::table('users')->where('role','member')->get();
        $addresses = [];
        foreach ($members as $idx=>$u) {
            $addresses[] = [
                'user_id'=>$u->id,
                'recipient_name'=>$u->name,
                'phone'=>$u->phone ?? '0800000000',
                'address'=>$u->address ?? '134 หมู่ 4 ตำบลบ่อแก้ว',
                'province'=>($idx % 5 === 0) ? 'เชียงราย' : (($idx % 7 === 0) ? 'กรุงเทพมหานคร' : 'เชียงใหม่'),
                'district'=>($idx % 5 === 0) ? 'เมืองเชียงราย' : (($idx % 7 === 0) ? 'เขตปทุมวัน' : 'สะเมิง'),
                'subdistrict'=>($idx % 5 === 0) ? 'เวียง' : (($idx % 7 === 0) ? 'ปทุมวัน' : 'บ่อแก้ว'),
                'postal_code'=>($idx % 7 === 0) ? '10330' : (($idx % 5 === 0) ? '57000' : '50250'),
                'is_default'=>true,
                'created_at'=>$u->created_at,'updated_at'=>$u->created_at,
            ];
        }
        DB::table('shipping_addresses')->insert($addresses);
    }

    private function seedCategoriesAndProducts($now): void
    {
        $productRows=[]; $inventoryRows=[]; $imageRows=[];
        foreach ($this->categories as $ci=>$cat) {
            $catId = DB::table('categories')->insertGetId([
                'name'=>$cat['name'],'name_en'=>$cat['name_en'],'slug'=>$cat['slug'],
                'description'=>'หมวดหมู่'.$cat['name'].' สำหรับทดสอบระบบสินค้า ยอดขาย และรายงาน',
                'description_en'=>$cat['name_en'].' demo category for products, sales and reports.',
                'is_active'=>true,'created_at'=>$now->copy()->subMonths(10-$ci),'updated_at'=>$now,
            ]);
            for ($i=1; $i<=9; $i++) {
                $sku = $cat['prefix'].'-'.str_pad((string)$i,3,'0',STR_PAD_LEFT);
                $price = $cat['base'] + (($i%5)*37) + ($ci*9);
                $created = $now->copy()->subDays(320 - (($ci*23+$i*11)%300));
                $productRows[] = [
                    'category_id'=>$catId,
                    'name'=>$cat['name'].' รุ่น '.$i,
                    'name_en'=>$cat['name_en'].' Model '.$i,
                    'slug'=>$cat['slug'].'-'.$i,
                    'description'=>'สินค้าเดโมสำหรับทดสอบระบบจริง: '.$cat['name'].' รุ่น '.$i.' ใช้ตรวจสอบตะกร้า สต็อก ใบเสร็จ ภาษี และรายงานยอดขาย',
                    'description_en'=>'Demo product for cart, stock, receipt, tax and sales report testing.',
                    'price'=>$price,'cost'=>round($price*0.45,2),'sku'=>$sku,'status'=>($i===9 && $ci%3===0) ? 'inactive' : 'active',
                    'featured'=>($i<=3),'added_at'=>$created,'created_at'=>$created,'updated_at'=>$created,
                ];
            }
        }
        DB::table('products')->insert($productRows);
        $products = DB::table('products')->orderBy('id')->get();
        foreach ($products as $idx=>$p) {
            $qty = match(true) {
                $idx % 17 === 0 => 0,
                $idx % 11 === 0 => rand(1,8),
                default => rand(12,95),
            };
            $inventoryRows[] = ['product_id'=>$p->id,'quantity'=>$qty,'low_stock_threshold'=>10,'created_at'=>$p->created_at,'updated_at'=>$now];
            $imageRows[] = ['product_id'=>$p->id,'path'=>'images/products/demo-product-'.str_pad((string)(($idx%36)+1),2,'0',STR_PAD_LEFT).'.svg','is_primary'=>true,'created_at'=>$p->created_at,'updated_at'=>$now];
        }
        DB::table('inventories')->insert($inventoryRows);
        DB::table('product_images')->insert($imageRows);

        $logs=[];
        $inventories = DB::table('inventories')->get();
        $adminId = DB::table('users')->where('role','admin')->value('id');
        foreach ($inventories as $inv) {
            $logs[] = ['inventory_id'=>$inv->id,'type'=>'add','quantity'=>max($inv->quantity,25),'note'=>'เพิ่มสต็อกตั้งต้นสำหรับ Demo Database','user_id'=>$adminId,'created_at'=>$now->copy()->subMonths(10),'updated_at'=>$now->copy()->subMonths(10)];
            if ($inv->quantity <= $inv->low_stock_threshold) {
                $logs[] = ['inventory_id'=>$inv->id,'type'=>'adjust','quantity'=>$inv->quantity,'note'=>'สินค้าทดสอบกลุ่มใกล้หมด/หมดสต็อก','user_id'=>$adminId,'created_at'=>$now->copy()->subDays(3),'updated_at'=>$now->copy()->subDays(3)];
            }
        }
        DB::table('inventory_logs')->insert($logs);
    }

    private function seedOrdersPaymentsShipmentsAndReviews($now): void
    {
        $members = DB::table('users')->where('role','member')->pluck('id')->all();
        $products = DB::table('products')->where('status','active')->get()->values();
        $adminId = DB::table('users')->where('role','admin')->value('id');
        $statuses = ['delivered','delivered','delivered','shipped','packed','preparing','paid','pending_payment','cancelled','delivery_failed'];
        $paymentMethods = ['bank_transfer','qr','bank_transfer','qr','cod'];
        $slips = ['slips/demo-slip-qr-valid.svg','slips/demo-slip-bank-valid.svg','slips/demo-slip-ocr-review.svg','slips/demo-slip-rejected.svg'];
        $orderCount = 620;
        for ($i=1; $i<=$orderCount; $i++) {
            $date = $now->copy()->subDays(($orderCount-$i) % 900)->setTime(($i*3)%23, ($i*7)%59, 0);
            $userId = $members[$i % count($members)];
            $status = $statuses[$i % count($statuses)];
            $paymentStatus = in_array($status, ['delivered','shipped','packed','preparing','paid']) ? 'approved' : (($status === 'cancelled' || $status === 'delivery_failed') ? 'rejected' : 'pending');
            $items=[]; $subtotal=0; $itemCount = 1 + ($i % 4);
            for ($j=0; $j<$itemCount; $j++) {
                $product = $products[($i*3+$j*7) % $products->count()];
                $qty = 1 + (($i+$j) % 3);
                $line = $qty * (float)$product->price;
                $subtotal += $line;
                $items[] = ['product'=>$product,'qty'=>$qty,'line'=>$line];
            }
            $shipping = ($i % 6 === 0) ? 100 : 50;
            if ($itemCount >= 4) $shipping += 50;
            $beforeVat = round(($subtotal + $shipping) / 1.07, 2);
            $vat = round(($subtotal + $shipping) - $beforeVat, 2);
            $withholding = ($i % 25 === 0) ? round($beforeVat * 0.03, 2) : 0;
            $total = round($subtotal + $shipping - $withholding, 2);
            $orderId = DB::table('orders')->insertGetId([
                'user_id'=>$userId,
                'order_number'=>'MYH'.$date->format('YmdHis').strtoupper(Str::random(6)),
                'status'=>$status,'payment_status'=>$paymentStatus,
                'subtotal'=>$subtotal,'shipping_fee'=>$shipping,'before_vat_amount'=>$beforeVat,'vat_amount'=>$vat,'withholding_tax_amount'=>$withholding,'shipping_rule_note'=>$shipping > 50 ? 'ต่างจังหวัด/จำนวนสินค้ามากกว่า 10 ชิ้น' : 'เชียงใหม่ 50 บาท',
                'total'=>$total,
                'shipping_address_snapshot'=>json_encode(['recipient_name'=>'ลูกค้าทดสอบ '.$i,'phone'=>'08'.str_pad((string)(20000000+$i),8,'0',STR_PAD_LEFT),'address'=>'134 หมู่ 4 ตำบลบ่อแก้ว อำเภอสะเมิง จังหวัดเชียงใหม่ 50250'], JSON_UNESCAPED_UNICODE),
                'needs_tax_invoice'=>$i % 9 === 0,
                'customer_tax_id'=>$i % 9 === 0 ? '111'.str_pad((string)$i,10,'0',STR_PAD_LEFT) : null,
                'customer_tax_name'=>$i % 9 === 0 ? 'บริษัท ลูกค้าทดสอบ '.$i.' จำกัด' : null,
                'customer_tax_address'=>$i % 9 === 0 ? '134 หมู่ 4 ตำบลบ่อแก้ว อำเภอสะเมิง จังหวัดเชียงใหม่ 50250' : null,
                'ordered_at'=>$date,'expires_at'=>$status==='pending_payment' ? $date->copy()->addDays(1) : null,
                'created_at'=>$date,'updated_at'=>$date->copy()->addHours(2),
            ]);
            $orderItems=[];
            foreach ($items as $it) {
                $orderItems[] = ['order_id'=>$orderId,'product_id'=>$it['product']->id,'product_name'=>$it['product']->name,'quantity'=>$it['qty'],'price'=>$it['product']->price,'total'=>$it['line'],'created_at'=>$date,'updated_at'=>$date];
            }
            DB::table('order_items')->insert($orderItems);
            $method = $paymentMethods[$i % count($paymentMethods)];
            $reviewStatus = $i % 8 === 0 ? 'needs_review' : ($i % 13 === 0 ? 'rejected' : 'passed');
            $score = $reviewStatus === 'passed' ? (($i%3)+3) : ($reviewStatus === 'needs_review' ? 2 : 0);
            DB::table('payments')->insert([
                'order_id'=>$orderId,'method'=>$method,'amount'=>$total,
                'slip_path'=>$method==='cod' ? null : $slips[$i % count($slips)],
                'slip_review_status'=>$method==='cod' ? 'not_required' : $reviewStatus,
                'slip_ocr_score'=>$method==='cod' ? 0 : $score,
                'slip_ocr_text'=>$method==='cod' ? null : 'ธนาคาร รายการสำเร็จ ยอดเงิน '.$total.' บาท วันที่ '.$date->format('d/m/Y H:i').' PromptPay Rai Khaisaeng Strawberry',
                'slip_ocr_note'=>$method==='cod' ? 'เก็บเงินปลายทาง ไม่ต้องตรวจสลิป' : ($reviewStatus==='passed' ? 'Demo: QR/OCR ผ่าน' : 'Demo: ให้แอดมินตรวจสอบ'),
                'status'=>$paymentStatus === 'approved' ? 'approved' : ($paymentStatus==='rejected' ? 'rejected' : 'pending'),
                'verified_by'=>$paymentStatus === 'approved' ? $adminId : null,
                'verified_at'=>$paymentStatus === 'approved' ? $date->copy()->addHours(1) : null,
                'reject_reason'=>$paymentStatus === 'rejected' ? 'ข้อมูลทดสอบ: สลิปไม่ถูกต้อง/ยกเลิกคำสั่งซื้อ' : null,
                'transaction_ref'=>'TXN'.$date->format('Ymd').str_pad((string)$i,6,'0',STR_PAD_LEFT),
                'created_at'=>$date->copy()->addMinutes(10),'updated_at'=>$date->copy()->addHours(1),
            ]);
            DB::table('shipments')->insert([
                'order_id'=>$orderId,
                'carrier'=>in_array($status,['shipped','delivered','delivery_failed']) ? ['Kerry Express','Flash Express','ไปรษณีย์ไทย'][$i%3] : null,
                'tracking_number'=>in_array($status,['shipped','delivered','delivery_failed']) ? 'RK'.str_pad((string)$i,10,'0',STR_PAD_LEFT).'TH' : null,
                'status'=>match($status){'delivered'=>'delivered','shipped'=>'shipped','packed'=>'preparing','preparing'=>'preparing', default=>'pending'},
                'shipped_at'=>in_array($status,['shipped','delivered','delivery_failed']) ? $date->copy()->addDays(1) : null,
                'delivered_at'=>$status==='delivered' ? $date->copy()->addDays(3) : null,
                'created_at'=>$date,'updated_at'=>$date,
            ]);
        }
        // Reviews after orders
        $reviewRows=[];
        $comments = ['รสชาติดีมาก แพ็กสินค้าดี','หอมสตรอว์เบอร์รี่ ชอบมาก','ส่งไว สินค้าคุณภาพดี','เหมาะเป็นของฝาก','จะกลับมาสั่งอีก','หวานกำลังดี ไม่เลี่ยน'];
        for ($i=1; $i<=360; $i++) {
            $p = $products[$i % $products->count()];
            $reviewRows[] = ['user_id'=>$members[$i % count($members)],'product_id'=>$p->id,'rating'=>4 + ($i % 2),'comment'=>$comments[$i % count($comments)],'status'=>$i%16===0?'pending':'approved','created_at'=>$now->copy()->subDays($i%365),'updated_at'=>$now->copy()->subDays($i%365)];
        }
        DB::table('reviews')->insert($reviewRows);
    }

    private function seedNotifications($now): void
    {
        $adminIds = DB::table('users')->whereIn('role',['admin','super_admin'])->pluck('id');
        $rows=[];
        foreach ($adminIds as $id) {
            $rows[] = ['user_id'=>$id,'title'=>'มีคำสั่งซื้อใหม่','message'=>'Demo Database: มีออเดอร์ใหม่รอตรวจสอบ','type'=>'order','read_at'=>null,'created_at'=>$now->copy()->subMinutes(40),'updated_at'=>$now->copy()->subMinutes(40)];
            $rows[] = ['user_id'=>$id,'title'=>'มีสลิปรอตรวจสอบ','message'=>'Demo Database: OCR ส่งรายการให้ Admin ตรวจเอง','type'=>'payment','read_at'=>null,'created_at'=>$now->copy()->subMinutes(20),'updated_at'=>$now->copy()->subMinutes(20)];
            $rows[] = ['user_id'=>$id,'title'=>'สินค้าใกล้หมด','message'=>'Demo Database: มีสินค้าใกล้หมดและหมดสต็อกสำหรับทดสอบหน้า Inventory','type'=>'inventory','read_at'=>null,'created_at'=>$now->copy()->subMinutes(10),'updated_at'=>$now->copy()->subMinutes(10)];
        }
        DB::table('notifications')->insert($rows);
    }
}
