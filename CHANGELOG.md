
## v22-22
- ใช้ฐานจาก v22-21 / v22-18 logic
- ปรับ Footer สีเขียวเข้ม `#1F4F38` และ `#183F2D`
- รักษา Rai Khaisaeng Theme สีหลักอื่น ๆ โดยเฉพาะข้อความ `#4A3E3D`
- เพิ่ม Motion Design แบบเบา ๆ เฉพาะ CSS/JS ใน Blade
- ไม่เพิ่ม Cursor Effect
- แก้ `package.json` ให้ถูกต้องและระบุว่าไม่จำเป็นต้องใช้ Vite build
- อัปเดต README_TH และ README_TH_VSCODE


## v22-13
- ปรับหน้าใบเสร็จจาก v22-12 ให้ที่อยู่ร้านค้าตัดบรรทัดได้อัตโนมัติเมื่อข้อความยาว เพื่อไม่ให้ชนกับกรอบเลขเอกสาร
- เพิ่มหัวข้อสิ่งที่ต้องติดตั้งไว้บนสุดของ README_TH ก่อนขั้นตอนการรัน

# v22-08 Logo Multilanguage Update

## Added
- เพิ่มโลโก้ภาษาไทยและภาษาอังกฤษแบบ PNG พื้นหลังโปร่งใส
- ภาษาอังกฤษใช้ข้อความ `Rai Khaisaeng Strawberry` และ `Community Enterprise`
- Navbar หน้าร้านสลับโลโก้ตามภาษา TH/EN อัตโนมัติ
- ใบเสร็จ/ใบกำกับภาษีเลือกโลโก้ตามภาษาได้
- หน้า `ตั้งค่าข้อมูลร้านค้า` อัปโหลดโลโก้ TH/EN, โลโก้ใบเสร็จ TH/EN และ Favicon ได้
- เพิ่ม Migration `2026_06_09_000900_add_multilingual_logo_settings.php`

## Changed
- Favicon เริ่มต้นเป็นรูปสตรอเบอร์รี่ใหม่
- ค่าเริ่มต้นชื่อภาษาอังกฤษเป็น `Rai Khaisaeng Strawberry`

## Run After Update
```bash
php artisan migrate
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

## v22-16 RBAC + Test Accounts + Social Login Guide
- ปรับสิทธิ์ Admin / Super Admin ให้แยกชัดตาม RBAC
- Admin ใช้งานงานประจำวัน เช่น สินค้า ออเดอร์ ชำระเงิน คลังสินค้า รายงาน และใบเสร็จ
- Super Admin เท่านั้นที่จัดการผู้ใช้งาน สิทธิ์ ธีม และตั้งค่าข้อมูลร้านค้าได้
- เพิ่มบัญชีทดสอบ user_test, admin_test, sb admin_test ใน Seeder
- เพิ่มคำอธิบายบัญชีทดสอบในหน้า Login
- เพิ่ม Social Login ในหน้า Register ด้วย Google / Facebook / LINE
- ปรับสิทธิ์ใบเสร็จ: User ดูเฉพาะของตัวเอง, Admin/Super Admin ดูได้ทุกออเดอร์


## v22-21
- Base project: v22-18 QR + OCR Flow
- Removed old user theme preset list and replaced with 12 new user/storefront presets
- Added Modern Minimal font stack: Prompt, Kanit, Bai Jamjuree, Inter, Roboto
- No cursor effects
- No motion design
- Added lightweight package.json to prevent npm install errors; npm is optional
- Updated README_TH.md and README_TH_VSCODE.md


---

## v22-23 Demo Production Database

เพิ่มชุดข้อมูลทดสอบระบบแบบสมจริง สำหรับใช้ Demo / Test / ตรวจ Dashboard และ Report

### ข้อมูลที่มีใน Seeder

- ผู้ใช้งานทดสอบ 60+ คน
- บัญชีทดสอบ `user_test@khaisaeng.test`, `admin_test@khaisaeng.test`, `sbadmin_test@khaisaeng.test`
- หมวดหมู่สินค้า 8 หมวด
- สินค้า 72 รายการ พร้อมรูปสินค้า SVG
- สต็อกปกติ / ใกล้หมด / หมดสต็อก
- ออเดอร์ย้อนหลังประมาณ 620 รายการ
- Payment / Slip demo สำหรับ QR + OCR + Admin Manual Review
- รีวิวสินค้า 360 รายการ
- ข้อมูลสำหรับ Dashboard: ยอดขายรายวัน รายเดือน รายปี สินค้าขายดี ลูกค้าซื้อเยอะ และสถิติรายงาน

### คำสั่งสร้างข้อมูลทดสอบใหม่ทั้งหมด

```bash
php artisan migrate:fresh --seed
php artisan storage:link
php artisan optimize:clear
```

### ไฟล์สำคัญ

```text
database/seeders/DemoProductionSeeder.php
database/sql/README_DEMO_DATABASE_TH.md
public/images/products/demo-product-*.svg
storage/app/public/slips/demo-slip-*.svg
```

## v24 - Responsive Design / Mobile First
- เพิ่มไฟล์ `public/css/responsive-v24.css` สำหรับปรับหน้าร้านและหลังบ้านบนมือถือ
- เพิ่มไฟล์ `public/js/responsive-v24.js` สำหรับ Sidebar Overlay ฝั่ง Admin และ Sticky Checkout บนมือถือ
- ปรับ Navbar มือถือให้เป็น Hamburger และจัด Search / Cart ให้ไม่ล้นจอ
- ปรับ Hero Banner, Product Card, Product Grid, Category Sidebar ให้เหมาะกับมือถือ
- ปรับ Cart บนมือถือจากตารางเป็น Card-like layout เพื่ออ่านง่ายขึ้น
- ปรับ Checkout เป็นแนวตั้งบนมือถือ พร้อมปุ่มสั่งซื้อแบบ Sticky ด้านล่าง
- ปรับ Footer ให้เรียงเป็นชั้นและลดความสูงของแผนที่บนมือถือ
- ปรับ Admin Sidebar เป็น Overlay บนมือถือ และทำตารางหลังบ้านให้ Scroll แนวนอนได้
- เพิ่มหัวข้อ Responsive Testing Checklist ใน README_TH และ README_TH_VSCODE
