# Rai Khaisaeng Strawberry Laravel Ready 95 v22-22

## สิ่งที่ต้องติดตั้งก่อนรันโปรแกรม

โปรเจกต์นี้เป็น Laravel 12 ต้องติดตั้ง/เตรียมสิ่งต่อไปนี้ก่อนรัน

### โปรแกรมหลัก
- XAMPP ที่มี PHP 8.2 ขึ้นไป
- Composer 2.x
- MySQL / MariaDB
- Git
- VS Code หรือ Editor ที่ถนัด

### PHP Extensions ที่ควรเปิดใน `C:\xampp\php\php.ini`
- `gd` ใช้กับรูปภาพ
- `zip` ใช้กับ Composer และ Package
- `fileinfo` ใช้ตรวจชนิดไฟล์ Upload
- `openssl` ใช้กับ HTTPS / Social Login
- `pdo_mysql` ใช้เชื่อมต่อ MySQL

ตรวจสอบเวอร์ชันด้วยคำสั่ง

```powershell
php -v
composer --version
```

### Package ที่ใช้กับ Social Login และ OCR ตรวจสลิป

รันคำสั่งนี้หลังแตกไฟล์ ZIP และอยู่ในโฟลเดอร์โปรเจกต์

```powershell
composer install
composer require laravel/socialite
composer require socialiteproviders/line
composer require thiagoalessio/tesseract_ocr
php artisan optimize:clear
```

> หมายเหตุ: ในไฟล์ `composer.json` ของ v22-22 ใส่ package เหล่านี้ไว้แล้ว ดังนั้นปกติรัน `composer install` ก็เพียงพอ แต่เพิ่มคำสั่ง `composer require ...` ไว้เพื่อกรณี package ยังไม่ถูกติดตั้งหรือ vendor หาย

### ติดตั้ง Tesseract OCR สำหรับตรวจสลิประดับ 2

ระบบตรวจสลิปแบบ OCR จะอ่านตัวอักษรในรูปสลิป ถ้าไม่ติดตั้ง Tesseract ระบบจะยังรับสลิปได้ แต่จะแสดงสถานะว่า “ต้องตรวจสอบ” ให้แอดมินตรวจเอง

#### Windows
1. ติดตั้ง Tesseract OCR for Windows
2. ตอนติดตั้งให้เลือกภาษา `Thai` และ `English`
3. เพิ่ม Path ของ Tesseract เข้า Environment Variables เช่น

```text
C:\Program Files\Tesseract-OCR
```

4. ปิด Terminal แล้วเปิดใหม่ จากนั้นตรวจสอบ

```powershell
tesseract --version
```

#### Ubuntu / Linux Server

```bash
sudo apt update
sudo apt install tesseract-ocr tesseract-ocr-tha
```

---

## วิธีรันบน XAMPP

1. แตก ZIP ไปที่โฟลเดอร์ที่ต้องการ เช่น `C:\xampp\htdocs\maeyangha_laravel_ready_95_v22-22`
2. เปิด XAMPP แล้ว Start Apache + MySQL
3. สร้าง database ชื่อ `maeyangha_shop`
4. เปิด Terminal ในโฟลเดอร์โปรเจกต์ แล้วรัน

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan serve
```

เข้าเว็บ: `http://127.0.0.1:8000`

---

## ตั้งค่า Social Login

> สำคัญ: ไม่ควรฝัง Client Secret ลงในโค้ดหรือ ZIP ที่ส่งให้คนอื่น ให้ใส่ในไฟล์ `.env` ของเครื่องตัวเองหรือ Hosting เท่านั้น

### Google Login

1. เข้า Google Cloud Console
2. ไปที่ `Google Auth Platform > Branding` แล้วกรอกข้อมูลแอป
3. ไปที่ `Audience` แล้วเพิ่ม Gmail ที่ใช้ทดสอบใน Test users
4. ไปที่ `Clients > Create OAuth Client`
5. เลือก `Web application`
6. เพิ่ม Authorized Redirect URI

Localhost:

```text
http://127.0.0.1:8000/auth/google/callback
```

Deploy จริง:

```text
https://yourdomain.com/auth/google/callback
```

ใส่ค่าใน `.env`

```env
GOOGLE_CLIENT_ID=ใส่ Client ID จาก Google
GOOGLE_CLIENT_SECRET=ใส่ Client Secret จาก Google
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

### Facebook Login

1. เข้า Meta for Developers
2. Create App
3. เพิ่ม Facebook Login
4. ตั้ง Valid OAuth Redirect URI

Localhost:

```text
http://127.0.0.1:8000/auth/facebook/callback
```

Deploy จริง:

```text
https://yourdomain.com/auth/facebook/callback
```

ใส่ค่าใน `.env`

```env
FACEBOOK_CLIENT_ID=ใส่ App ID
FACEBOOK_CLIENT_SECRET=ใส่ App Secret
FACEBOOK_REDIRECT_URI=http://127.0.0.1:8000/auth/facebook/callback
```

### LINE Login

1. รัน `composer require socialiteproviders/line`
2. เข้า LINE Developers
3. Create Provider / Create LINE Login Channel
4. ตั้ง Callback URL

Localhost:

```text
http://127.0.0.1:8000/auth/line/callback
```

Deploy จริง:

```text
https://yourdomain.com/auth/line/callback
```

ใส่ค่าใน `.env`

```env
LINE_CLIENT_ID=ใส่ Channel ID
LINE_CLIENT_SECRET=ใส่ Channel Secret
LINE_REDIRECT_URI=http://127.0.0.1:8000/auth/line/callback
```

หลังแก้ `.env` ทุกครั้ง ให้รัน

```powershell
php artisan optimize:clear
```

---

## ระบบ OCR ตรวจสลิปโอนเงิน ระดับ 2

เมื่อลูกค้าอัปโหลดสลิป ระบบจะตรวจไฟล์ดังนี้

1. ตรวจว่าเป็นรูป `jpg, jpeg, png` และขนาดไม่เกิน 5MB
2. ใช้ Tesseract OCR อ่านข้อความในรูป
3. ตรวจคำสำคัญ เช่น `ธนาคาร`, `โอน`, `ยอดเงิน`, `เลขอ้างอิง`, `PromptPay`, `Transfer`, `Amount`, `Reference`
4. ถ้าพบหลายคำ ระบบจะแสดง Badge `ผ่าน OCR`
5. ถ้าพบบางคำ ระบบจะแสดง `ต้องตรวจสอบ`
6. ถ้าไม่พบคำที่คล้ายสลิป ระบบจะแจ้งให้ลูกค้าอัปโหลดใหม่ ไม่ส่งข้อมูลเข้าแอดมิน

> ระบบนี้เป็นการช่วยคัดกรองเบื้องต้น ไม่ใช่การยืนยันเงินจริง 100% แอดมินยังต้องตรวจยอดเงินและเลขอ้างอิงก่อนอนุมัติ

---

## Logout หลังบ้าน

v22-22 เพิ่มปุ่มออกจากระบบ 2 จุด

- มุมขวาบนของ Admin / Super Admin
- แถบ Sidebar ด้านซ้ายล่าง

มีเมนู `ไปหน้าร้าน`, `สลับบัญชี`, และ `ออกจากระบบ`

---

## บัญชีทดสอบ

| ประเภท | Email | Password | ใช้ทดสอบ |
|---|---|---|---|
| Member / User | `user_test@khaisaeng.test` | `password` | หน้าร้าน, ตะกร้า, Checkout, อัปโหลดสลิป, ประวัติคำสั่งซื้อ, ใบเสร็จของตัวเอง |
| Admin | `admin_test@khaisaeng.test` | `password` | Dashboard, สินค้า, หมวดหมู่, ออเดอร์, ชำระเงิน, คลังสินค้า, รายงาน, ออกใบเสร็จ |
| Super Admin | `sbadmin_test@khaisaeng.test` | `password` | ทุกอย่างของ Admin + จัดการผู้ใช้งาน/สิทธิ์, ตั้งค่าร้านค้า, ธีม, Social, Favicon |

---

# สิ่งที่ต้องติดตั้งก่อนรันโปรแกรม

โปรเจกต์นี้เป็น Laravel 12 ต้องเตรียมโปรแกรมและส่วนเสริมต่อไปนี้ก่อนเริ่มรัน

## โปรแกรมหลัก
- XAMPP ที่มี PHP 8.2 ขึ้นไป
- Composer 2.x
- MySQL / MariaDB ผ่าน XAMPP
- Git สำหรับบางแพ็กเกจของ Composer
- VS Code หรือ Editor สำหรับแก้ไขไฟล์

## PHP Extensions ที่ต้องเปิดใน `C:\xampp\php\php.ini`
- `gd` ใช้กับรูปสินค้า/รูปใบเสร็จ
- `zip` ใช้กับ Composer และ Package บางตัว
- `fileinfo` ใช้ตรวจสอบชนิดไฟล์ Upload
- `openssl` ใช้เชื่อมต่อ HTTPS/Composer/Social Login
- `pdo_mysql` ใช้เชื่อมต่อ MySQL

ตรวจสอบเวอร์ชันด้วยคำสั่ง

```powershell
php -v
composer --version
```

---
# วิธีรันบน XAMPP

1. แตก ZIP ไปที่ `C:\xampp\htdocs\maeyangha_laravel_ready_95` หรือโฟลเดอร์ที่ต้องการ
2. เปิด XAMPP แล้ว Start Apache + MySQL
3. สร้าง database ชื่อ `maeyangha_shop`
4. เปิด Terminal ในโฟลเดอร์โปรเจกต์ แล้วรัน

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

เข้าเว็บ: http://127.0.0.1:8000

## บัญชีทดสอบ

ใช้บัญชีต่อไปนี้สำหรับทดสอบแต่ละส่วนของระบบหลังรัน `php artisan migrate --seed`

| ประเภท | Email | Password | ใช้ทดสอบ |
|---|---|---|---|
| Member / User | `user_test@khaisaeng.test` | `password` | หน้าร้าน, ตะกร้า, Checkout, อัปโหลดสลิป, ประวัติคำสั่งซื้อ, ใบเสร็จของตัวเอง |
| Admin | `admin_test@khaisaeng.test` | `password` | Dashboard, สินค้า, หมวดหมู่, ออเดอร์, ชำระเงิน, คลังสินค้า, รายงาน, ออกใบเสร็จ |
| Super Admin | `sbadmin_test@khaisaeng.test` | `password` | ทุกอย่างของ Admin + จัดการผู้ใช้งาน/สิทธิ์, ตั้งค่าร้านค้า, ธีม, Social, Favicon |

บัญชีเดิมยังมีไว้สำหรับอ้างอิง:
- Super Admin เดิม: `admin@maeyangha.test` / `password`
- Member เดิม: `member@maeyangha.test` / `password`

## สิทธิ์การใช้งาน Admin / Super Admin

### Admin
Admin ใช้สำหรับงานประจำวันของร้าน เช่น จัดการสินค้า หมวดหมู่ คลังสินค้า ออเดอร์ อนุมัติ/ปฏิเสธการชำระเงิน ดูรายงาน และออกใบเสร็จ

### Super Admin
Super Admin ใช้สำหรับบริหารระบบ เช่น ทุกอย่างที่ Admin ทำได้ + จัดการผู้ใช้งาน/สิทธิ์ เพิ่ม/ลบ Admin ตั้งค่าข้อมูลร้านค้า โลโก้ Favicon Social Link ตั้งค่าธีม และค่าระบบสำคัญ

> เหตุผลที่แยกสิทธิ์: เพื่อป้องกัน Admin ทั่วไปแก้ค่าระบบหลักโดยไม่ได้รับอนุญาต และให้สอดคล้องกับหลัก Role-Based Access Control (RBAC)

## Social Login: Google / Facebook / LINE

ระบบติดตั้ง Laravel Socialite แล้ว และมีปุ่มเข้าสู่ระบบ/สมัครสมาชิกด้วย Google, Facebook และ LINE ในหน้า Login/Register

ให้ใส่ค่าใน `.env` ก่อนใช้งานจริง:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=http://127.0.0.1:8000/auth/facebook/callback

LINE_CLIENT_ID=
LINE_CLIENT_SECRET=
LINE_REDIRECT_URI=http://127.0.0.1:8000/auth/line/callback
```

เมื่อผู้ใช้ Social Login ครั้งแรก ระบบจะสร้างบัญชี `member` ให้อัตโนมัติ

## หมายเหตุ
ไฟล์นี้ไม่รวมโฟลเดอร์ `vendor` ตามมาตรฐาน Laravel จึงต้องรัน `composer install` ก่อนใช้งานจริง


---

# วิธี Deploy บน Hosting ฟรีแบบเข้าใจง่าย

> หมายเหตุ: Laravel ใช้ได้ดีที่สุดบน Hosting ที่รองรับ PHP 8.2+, Composer, MySQL และสามารถตั้งค่า Document Root ไปที่โฟลเดอร์ `public` ได้ ถ้า Hosting ฟรีไม่รองรับ Composer หรือ SSH อาจต้อง Build/Upload ไฟล์จากเครื่องเราแทน

## ตัวเลือกที่แนะนำสำหรับทดลองส่งงาน
1. สมัคร Hosting ฟรี เช่น InfinityFree หรือ 000webhost/บริการที่รองรับ PHP + MySQL
2. สร้าง Database MySQL บน Hosting แล้วจดข้อมูล DB Host, DB Name, Username, Password
3. บนเครื่องเรา รันคำสั่งเตรียมไฟล์ก่อนอัปโหลด

```powershell
composer install --no-dev --optimize-autoloader
copy .env.example .env
php artisan key:generate
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

4. แก้ไฟล์ `.env` สำหรับ Hosting

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.example
DB_CONNECTION=mysql
DB_HOST=ใส่ DB Host ของ Hosting
DB_PORT=3306
DB_DATABASE=ชื่อฐานข้อมูล
DB_USERNAME=ชื่อผู้ใช้ฐานข้อมูล
DB_PASSWORD=รหัสผ่านฐานข้อมูล
```

5. อัปโหลดไฟล์ทั้งหมดขึ้น Hosting โดยให้เว็บชี้เข้าโฟลเดอร์ `public`
   - ถ้า Hosting เลือก Document Root ได้ ให้ตั้งเป็น `/public`
   - ถ้าเลือกไม่ได้ ให้ย้ายไฟล์ใน `public` ไปไว้ `htdocs/public_html` แล้วแก้ path ใน `index.php` ให้ชี้กลับไปยังโฟลเดอร์ Laravel
6. Import Database
   - วิธีง่าย: รันบนเครื่องให้สมบูรณ์ก่อน แล้ว Export SQL จาก phpMyAdmin
   - จากนั้น Import SQL เข้า phpMyAdmin ของ Hosting
7. สร้าง symbolic link สำหรับรูป Upload
   - ถ้า Hosting มี SSH: `php artisan storage:link`
   - ถ้าไม่มี SSH: สร้างโฟลเดอร์ `public/storage` แล้วอัปโหลดไฟล์จาก `storage/app/public` ไปไว้ข้างใน
8. ทดสอบเข้าเว็บจริง ตรวจหน้า Login, สินค้า, ตะกร้า, Checkout, ใบเสร็จ, Admin

## Checklist ก่อนส่งอาจารย์
- `APP_DEBUG=false`
- Database ใช้งานจริงได้
- รูปสินค้าแสดงครบ
- Upload Slip ได้
- ใบเสร็จเปิดได้
- Admin Login ได้
- ห้ามเผยแพร่ไฟล์ `.env` ให้คนอื่นเห็น

---

# Rai Khaisaeng Strawberry Laravel Ready 95 v20

โปรเจกต์ Laravel 12 สำหรับเว็บขายสินค้าออนไลน์วิสาหกิจชุมชนแปรรูปสตรอว์เบอร์รีไร่ไขแสงสตรอเบอร์รี่

## ความต้องการระบบ
- PHP 8.2 ขึ้นไป
- Composer 2.x
- MySQL / MariaDB ผ่าน XAMPP
- เปิด PHP extensions: `gd`, `zip`, `fileinfo`, `openssl`, `pdo_mysql`

## วิธีรันบน XAMPP
1. แตก ZIP ไปที่โฟลเดอร์ที่ต้องการ เช่น `C:\Users\skybl\Desktop\maeyangha_laravel_ready_95_v16_status_product_brand_fix`
2. เปิด XAMPP แล้ว Start Apache + MySQL
3. สร้าง database ชื่อ `maeyangha_shop` หรือชื่อที่ต้องการ
4. เปิด terminal ในโฟลเดอร์โปรเจกต์ แล้วรัน

```powershell
composer install
copy .env.example .env
php artisan key:generate
```

5. แก้ไฟล์ `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=maeyangha_shop
DB_USERNAME=root
DB_PASSWORD=
```

6. สร้างตารางและข้อมูลตัวอย่าง

```powershell
php artisan migrate --seed
php artisan storage:link
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan serve
```

เข้าเว็บ: `http://127.0.0.1:8000`

## บัญชีทดสอบ

ใช้บัญชีต่อไปนี้สำหรับทดสอบแต่ละส่วนของระบบหลังรัน `php artisan migrate --seed`

| ประเภท | Email | Password | ใช้ทดสอบ |
|---|---|---|---|
| Member / User | `user_test@khaisaeng.test` | `password` | หน้าร้าน, ตะกร้า, Checkout, อัปโหลดสลิป, ประวัติคำสั่งซื้อ, ใบเสร็จของตัวเอง |
| Admin | `admin_test@khaisaeng.test` | `password` | Dashboard, สินค้า, หมวดหมู่, ออเดอร์, ชำระเงิน, คลังสินค้า, รายงาน, ออกใบเสร็จ |
| Super Admin | `sbadmin_test@khaisaeng.test` | `password` | ทุกอย่างของ Admin + จัดการผู้ใช้งาน/สิทธิ์, ตั้งค่าร้านค้า, ธีม, Social, Favicon |

บัญชีเดิมยังมีไว้สำหรับอ้างอิง:
- Super Admin เดิม: `admin@maeyangha.test` / `password`
- Member เดิม: `member@maeyangha.test` / `password`

## หมายเหตุสำคัญ
ไฟล์ ZIP ไม่รวมโฟลเดอร์ `vendor` ตามมาตรฐาน Laravel จึงต้องรัน `composer install` ก่อนใช้งานจริง

---

# บันทึกการอัปเดตตั้งแต่แรกถึงล่าสุด

## v1: วิเคราะห์ระบบจากเอกสารโครงงาน
- กำหนดขอบเขตระบบเว็บขายสินค้าออนไลน์สำหรับวิสาหกิจชุมชนไร่ไขแสงสตรอเบอร์รี่
- แบ่งผู้ใช้งานเป็น Guest, Member, Admin, Super Admin
- วาง Module หลัก ได้แก่ Product, Cart, Order, Payment, Shipping, Inventory, Review, Notification, Dashboard, Report

## v2: Production Ready Scaffold
- สร้างโครงสร้าง Laravel สำหรับระบบหลังบ้านและหน้าร้าน
- เตรียมโครงสร้าง migration/model/controller/view เบื้องต้น
- เตรียม SB Admin 2 Dashboard แบบเริ่มต้น

## v3: Laravel Ready 95 โครงสร้างพร้อมติดตั้ง
- เพิ่ม README วิธีรันบน XAMPP
- เพิ่ม seeder หมวดหมู่สินค้า
- เพิ่ม controller/view หลักสำหรับหน้าร้านและหลังบ้าน

## v4: ระบบหลักรันได้จริง
- Laravel 12 source structure พร้อมรันหลัง `composer install`
- Login/Register ใช้งานจริง
- Role Middleware: member, staff, admin, super_admin
- Dashboard หลังบ้านธีม SB Admin 2 style พร้อม Chart.js
- CRUD สินค้า, หมวดหมู่, สมาชิก, ออเดอร์, การชำระเงิน, คลังสินค้า
- ตะกร้า, Checkout, Upload Slip, ตัด Stock อัตโนมัติ
- Payment Approve/Reject
- Shipping status / tracking controller
- Reports summary
- Seeder หมวดหมู่: สตรอว์เบอร์รีสด, สตรอว์เบอร์รีอบแห้ง, น้ำสตรอว์เบอร์รี, แยม, ขนมแปรรูป, ของฝาก

## v5: UI หน้าร้านและ Footer
- เพิ่มปุ่ม `เพิ่มสินค้า` ข้างปุ่มรายละเอียดสินค้า
- Guest กดเพิ่มสินค้าแล้วพาไปหน้า Login
- Member เพิ่มสินค้าลงตะกร้าได้ทันทีโดยไม่ต้องเข้าหน้ารายละเอียด
- เพิ่มเลขจำนวนสินค้าบนไอคอนตะกร้า
- เพิ่มแบนเนอร์โฆษณายาวหน้าแรก
- เพิ่ม Footer ข้อมูลโรงงาน/แผนที่/ติดต่อ/โซเชียล
- Navbar มีค้นหา, เปลี่ยนภาษา TH/EN, Dropdown หมวดสินค้า
- Admin สมาชิกเพิ่มคอลัมน์เบอร์โทรศัพท์
- แก้การแสดงรูปสินค้า ทั้งหน้าร้านและหลังบ้าน
- ปรับ UI หน้าร้านกับหลังบ้านให้ดูง่ายขึ้น

## v6: Cart และ Checkout
- เพิ่มสินค้าแบบ AJAX ไม่เด้งขึ้นบนหน้า
- หมวดหมู่ใน Navbar เชื่อมไปหน้าสินค้าตามหมวด
- ตะกร้าใช้ปุ่ม `+ / −` แทนช่องกรอกจำนวน
- จัด Layout ตะกร้าใหม่ และวางปุ่ม Checkout ด้านขวา
- หน้า Checkout แสดงรายการสินค้า ราคา จำนวน และยอดรวม
- QR Payment แสดงยอดเงินและรูป QR
- โอนธนาคารแสดงเลขบัญชี กสิกรไทย `0801882323` ชื่อ `จิรวัฒน์ โปธา`
- อัปโหลดสลิปแล้วโยงไปหน้า Admin ชำระเงินและออเดอร์
- เพิ่มมินิแมพและลิงก์ Google Maps
- เพิ่มเบอร์ติดต่อ 3 คน

## v7: Member Profile
- เพิ่มเมนูผู้ใช้แบบ Dropdown: บัญชีของฉัน / การซื้อของฉัน / ออกจากระบบ
- เพิ่มหน้าโปรไฟล์สมาชิก
- แก้ไขชื่อ อีเมล เบอร์โทร รหัสผ่าน และที่อยู่หลักได้
- เพิ่ม/แก้ไข/ลบที่อยู่จัดส่ง
- ตั้งที่อยู่เริ่มต้นได้
- หน้า Checkout ดึงที่อยู่จากบัญชีของฉันไปใช้ได้
- ปรับหน้าประวัติคำสั่งซื้อให้สวยและใช้ง่ายขึ้น

## v8: Address Dropdown / Social Login / Dashboard
- เพิ่ม Dropdown จังหวัด → อำเภอ → ตำบล → รหัสไปรษณีย์ ในหน้าโปรไฟล์และ Checkout
- เพิ่ม Social Login: Google / Facebook / LINE ผ่าน Laravel Socialite
- ปรับปุ่มจำนวนสินค้าในตะกร้าให้ไม่แตก CSS และใช้ปุ่ม + / - เท่านั้น
- เพิ่ม Dashboard วิเคราะห์รายเดือนรายปี, เส้นแนวโน้มผลิตภัณฑ์, เส้นยอดขาย, เส้นหมวดหมู่
- เพิ่มส่วน Promotion Insight เพื่อช่วยวางแผนกระตุ้นยอดขาย

## v9: Database Address + Checkout Fix
- แก้ Error หน้า Checkout เมื่อกดชำระแล้วตะกร้าว่าง: ระบบจะพากลับไปหน้าตะกร้าพร้อมข้อความแจ้งเตือน แทนการขึ้น 422
- Dropdown จังหวัด → อำเภอ → ตำบล → รหัสไปรษณีย์ เปลี่ยนเป็นดึงจาก Database ผ่านตาราง `thai_provinces`, `thai_districts`, `thai_subdistricts` และ API `/api/address/...`
- Seeder เพิ่มข้อมูลที่อยู่ตัวอย่าง เช่น เชียงใหม่/สะเมิง/บ่อแก้ว/50250 เพื่อให้ทดสอบระบบได้ทันที
- Seeder เพิ่มรูปสินค้าทดสอบใน `public/images/products` และผูกเข้ากับสินค้า เพื่อใช้ทดสอบก่อนเปลี่ยนเป็นรูปจริง
- ปรับหน้า Report Layout ให้เป็น Dashboard Grid Layout มี Summary Cards และ Promotion Insight

## v10: Inventory / Report / Address 77 จังหวัด / Product Layout
- หน้า Admin > คลังสินค้า ปรับเป็น Dashboard Card + ตารางใช้งานง่าย
- แสดงจำนวนสินค้าทั้งหมด, สินค้าใกล้หมด, สินค้าหมด, จำนวนรวมในคลัง
- สามารถค้นหาสินค้า และกดดูเฉพาะสินค้าใกล้หมดได้
- ปรับจำนวนคงเหลือและจำนวนขั้นต่ำได้ในตารางเดียว
- การ์ด `สินค้าใกล้หมด` ในหน้า Admin > รายงาน สามารถกดไปยังรายการสินค้าใกล้หมดได้
- เพิ่มตารางแสดงสินค้าที่ใกล้หมด 10 รายการแรกเพื่อให้ตรวจสอบเร็วขึ้น
- Seeder เพิ่มรายชื่อจังหวัดไทยครบ 77 จังหวัด และมีข้อมูลอำเภอ/ตำบลตัวอย่างสำหรับทดสอบ
- แก้ปัญหาหน้าผลิตภัณฑ์มีลูกศร Pagination ใหญ่ผิดปกติ
- แก้ไขเบอร์ติดต่อคุณอ๋อยเป็น `081-033-4893`

## v11: Full Thai Address / Inventory Buttons / Pagination
- เพิ่ม Seeder `ThaiAddressFullSeeder` สำหรับข้อมูลที่อยู่ไทยครบชุดจากไฟล์ `thai-administrative-division-full-my-sql.sql`
- ระบบรองรับตาราง `thai_provinces`, `thai_districts`, `thai_subdistricts`
- Checkout และ Profile ดึงข้อมูลแบบ Dropdown ตามลำดับ จังหวัด → อำเภอ/เขต → ตำบล/แขวง → รหัสไปรษณีย์
- หน้า Products ไม่แสดงข้อความ `Showing 1 to ...`
- ปรับ Pagination ใหม่ ไม่ใช้ SVG ขนาดใหญ่
- หน้า Admin > คลังสินค้า ใช้ปุ่ม `+` และ `-` แทนการพิมพ์ตัวเลขโดยตรง
- เพิ่มคำอธิบายช่องซ้าย/ขวาใน Inventory

## v12: Seed Fix
- แก้ปัญหา Seed ที่อยู่ไทย กรณี `zip_code` บางตำบลเป็นค่าว่าง
- เพิ่ม migration ให้ `zip_code` ใน `thai_subdistricts` รองรับ nullable
- ปรับ Seeder ให้ fallback เป็น `00000` เมื่อไม่พบ zip_code

## v13: Admin Products + Order Management Layout
- แก้ Error หน้า Admin > สินค้า: `Undefined variable $img`
- ปรับหน้า Admin > Orders เป็น Order Management Layout
- เพิ่ม Summary Cards: ออเดอร์ทั้งหมด, รอชำระเงิน, กำลังจัดส่ง, ยอดรวม
- เพิ่มแถบสถานะออเดอร์สำหรับกรอง: รอชำระเงิน, ชำระเงินแล้ว, เตรียมสินค้า, กำลังจัดส่ง, จัดส่งแล้ว, ยกเลิก
- เพิ่มระบบค้นหาเลขออเดอร์/ชื่อลูกค้า/อีเมล และกรองวันที่
- ตารางออเดอร์แสดงวันที่, เลขออเดอร์, ลูกค้า, ยอดเงิน, วิธีชำระเงิน, การจัดส่ง, สถานะ, ปุ่มจัดการ
- หน้ารายละเอียดออเดอร์แสดงสถานะปัจจุบัน, ข้อมูลลูกค้าและที่อยู่, รายการสินค้า, SKU, ประวัติการทำรายการ, ข้อมูลชำระเงิน
- เพิ่มปุ่มพิมพ์ใบปะหน้าพัสดุและพิมพ์ใบเสร็จเบื้องต้น
- ปรับ Inventory ไม่ให้ปุ่มและช่องจำนวนตกบรรทัด
- เปลี่ยนชื่อ `คุณอ้อน` เป็น `คุณอ๋อย`

---

# ข้อมูลที่อยู่ไทย

ระบบใช้ฐานข้อมูลจริง ไม่ hardcode ใน Controller หรือ JavaScript:

- `thai_provinces`
- `thai_districts`
- `thai_subdistricts`

หากต้องการใช้ข้อมูลจากแหล่งภายนอก สามารถ import SQL จาก:

```text
https://github.com/codesanook/thailand-administrative-division-province-district-subdistrict-sql/blob/master/thai-administrative-division-full-my-sql.sql
```

หรือใช้ Seeder ที่แนบมาแล้ว:

```powershell
php artisan db:seed --class=ThaiAddressFullSeeder
```

ถ้าเป็นฐานข้อมูลทดสอบและต้องการล้างข้อมูลใหม่ทั้งหมด:

```powershell
php artisan migrate:fresh --seed
```

# Social Login (.env)

ระบบมีปุ่ม Google / Facebook / LINE แล้ว แต่ต้องตั้งค่า Client ID และ Secret ก่อนใช้งานจริง:

```env
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback

FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=http://127.0.0.1:8000/auth/facebook/callback

LINE_CLIENT_ID=
LINE_CLIENT_SECRET=
LINE_REDIRECT_URI=http://127.0.0.1:8000/auth/line/callback
```

หลังแก้ไฟล์ `.env` ให้รัน:

```powershell
php artisan config:clear
php artisan route:clear
php artisan view:clear
```
## อัปเดต v14
- เพิ่มปุ่มเปลี่ยนสถานะออเดอร์จากหน้ารายการออเดอร์ได้ทันที ไม่ต้องเข้าหน้ารายละเอียด
- เพิ่มสถานะออเดอร์ให้ครบขึ้น: เตรียมสินค้า, จัดเสร็จแล้ว, กำลังจัดส่ง, จัดส่งแล้ว, จัดส่งไม่สำเร็จ, ยกเลิก
- ปรับ Layout หน้า Order Management ให้ค้นหา กรองสถานะ และจัดการสถานะได้รวดเร็ว
- ปรับหน้า Product Category หลังบ้านให้มี Header, Breadcrumb, Summary และลิงก์ดูสินค้าตามหมวด
- ปรับหน้าหมวดหมู่/หน้าสินค้าฝั่งลูกค้าให้มี Category Banner, Sidebar Filter, Product Grid และ Pagination ที่ใช้งานง่าย


## อัปเดต v15
- แถบสถานะออเดอร์หลังบ้านแสดงจำนวนของแต่ละสถานะ เช่น ทั้งหมด, รอชำระเงิน, ชำระเงินแล้ว, เตรียมสินค้า, จัดเสร็จแล้ว, กำลังจัดส่ง, จัดส่งแล้ว, จัดส่งไม่สำเร็จ, ยกเลิก
- ปรับหน้า Admin Product Backoffice ตามแนวทางที่ให้มา: Header + Filters + Product Data Table + Bulk Actions + Pagination
- หน้า Product List เพิ่มปุ่มเพิ่มสินค้าใหม่, ค้นหาชื่อ/SKU/รายละเอียด, กรองหมวดหมู่, กรองสถานะพร้อมขาย/ปิดการขาย, กรองคลังสินค้าใกล้หมด/หมด
- ตารางสินค้าเรียงคอลัมน์ใหม่: Checkbox, รูปภาพ, ชื่อสินค้า + SKU, หมวดหมู่, ราคา, สต๊อก, สถานะ, จัดการ
- เพิ่ม Bulk Actions สำหรับเปิดการขาย, ปิดการขาย, ลบสินค้าที่เลือกหลายรายการ
- เพิ่ม Inline Editing สำหรับราคา, สต๊อก และสถานะ จากหน้ารายการสินค้าได้ทันที
- ปรับหน้าเพิ่ม/แก้ไขสินค้าเป็น Two-Column Layout: คอลัมน์ซ้ายข้อมูลหลัก 70%, คอลัมน์ขวารูปภาพ/หมวดหมู่/สถานะ/SEO 30%
- เพิ่ม Product Media แบบ Drag & Drop UI, Visibility/Status, Featured, SEO Settings และ Sticky Action Bar สำหรับบันทึกข้อมูล
- เพิ่มคำอธิบาย UX/UI ของหน้า Product Backoffice ลงใน README เพื่อใช้ประกอบรายงานและนำเสนอ

## อัปเดต v16
- เปลี่ยนชื่อระบบและข้อความหน้าร้าน/หลังบ้านจาก บ้านแม่ยางห้า เป็น ไร่ไขแสงสตรอเบอร์รี่ ทั้งหมด
- แก้ Slug หมวดหมู่จากตัวเลข/ค่าผิดปกติให้เป็นตัวอักษรอ่านง่าย เช่น `strawberry-fresh`, `processed-products`, `drinks`
- เพิ่ม Migration สำหรับปรับ Slug หมวดหมู่เดิมในฐานข้อมูลให้เป็น URL ที่อ่านง่าย
- ปรับหน้า Admin Product List เป็นแบบ Production Ready: ราคาและสต๊อกแสดงเป็นข้อความธรรมดา ลดความรกของปุ่มบันทึกรายแถว
- เพิ่มสถานะสต๊อกในหน้าสินค้าแอดมิน: ปกติ / ใกล้หมด / หมด พร้อมจำนวนขั้นต่ำแจ้งเตือน
- หน้าคำสั่งซื้อเปลี่ยนตัวเลขสถานะจาก Badge หลังปุ่มกรอง ไปเป็น Summary Cards แยกทุกสถานะด้านบน เพื่อดูภาพรวมได้ชัดเจนกว่า
- แถบกรองสถานะออเดอร์ยังคงใช้กรองได้ แต่ไม่แสดงตัวเลขหลังข้อความแล้ว

## อัปเดต v17
- หน้า Order Management เพิ่มปุ่ม `บันทึกทั้งหมด` สำหรับเปลี่ยนสถานะหลายออเดอร์ในครั้งเดียว ไม่ต้องกดบันทึกทีละแถว
- หน้า Order Management เพิ่มระบบจำตำแหน่ง Scroll หลังบันทึกสถานะ เพื่อไม่ให้หน้าจอเด้งกลับขึ้นบน
- หน้า Inventory เปลี่ยนเป็นบันทึกหลายรายการด้วยปุ่ม `บันทึกทั้งหมด` พร้อมปุ่ม + / - สำหรับปรับจำนวนคงเหลือและขั้นต่ำแจ้งเตือน
- หน้า Inventory เพิ่มระบบจำตำแหน่ง Scroll หลังบันทึก เพื่อให้ผู้ดูแลทำงานต่อจากตำแหน่งเดิมได้ทันที
- หน้า Admin Product List เพิ่มระบบจำตำแหน่ง Scroll เมื่อกดแก้ไขสินค้าและกลับมายังหน้ารายการสินค้า เช่น แก้สินค้าที่อยู่ล่างสุดแล้วกลับมายังตำแหน่งเดิม
- ปรับ UX หลังบ้านให้เหมาะกับการจัดการข้อมูลจำนวนมาก ลดการเลื่อนหน้าจอซ้ำและลดจำนวนครั้งที่ต้องกดบันทึก

## อัปเดต v18
- ปรับหน้า Admin Users Management ใหม่ตามแนว Backoffice ที่เน้นความปลอดภัยและจัดการสิทธิ์ได้ง่าย
- เพิ่ม Summary Cards: ผู้ใช้งานทั้งหมด, Active, Suspended, Admin/Super Admin, Member
- เพิ่มค้นหาผู้ใช้งานจากชื่อ, อีเมล, เบอร์โทรศัพท์ หรือ User ID
- เพิ่มตัวกรอง Role และสถานะบัญชี
- เพิ่ม Role-Based Badges แยกสี: Super Admin/Admin/Staff/Member
- เพิ่ม Quick Actions: ดูรายละเอียด, แก้ไข, รีเซ็ตรหัสผ่าน, ระงับ/เปิดใช้งานบัญชี
- เพิ่มหน้าเพิ่ม/แก้ไขผู้ใช้งานแบบ Two-Column Layout พร้อม Sticky Action Bar
- เพิ่มหน้ารายละเอียดผู้ใช้งาน แสดงข้อมูลส่วนตัว, ที่อยู่จัดส่ง, ประวัติคำสั่งซื้อ, Activity Logs และส่วน Role & Security
- เพิ่ม Double Confirmation ก่อนรีเซ็ตรหัสผ่าน ระงับบัญชี และลบบัญชีถาวร

## อัปเดต v19
- เพิ่มระบบเปลี่ยนภาษาเฉพาะฝั่ง Guest / Member
- รองรับภาษาไทยและภาษาอังกฤษผ่าน `lang/th.json` และ `lang/en.json`
- เพิ่ม route `/language/{locale}` สำหรับสลับภาษา
- เพิ่ม Middleware `SetLocale` เพื่ออ่านภาษาจาก Session
- ปรับ Navbar / Footer / หน้าร้าน / สินค้า / ตะกร้า / Checkout / Login / Register / โปรไฟล์ / คำสั่งซื้อ ให้รองรับระบบภาษา
- ฝั่ง Admin / Super Admin ยังคงใช้ภาษาไทยตามเดิม ไม่บังคับใช้ระบบสองภาษา

## อัปเดต v20
- แก้ Navbar ฝั่ง Guest / Member / Admin / Super Admin ไม่ให้คำว่า `ผลิตภัณฑ์ / Products` ซ้ำกัน โดยเหลือเมนู Dropdown ผลิตภัณฑ์เพียงจุดเดียว
- เพิ่มระบบแปลชื่อสินค้าและหมวดหมู่จากฐานข้อมูลด้วยฟิลด์ `name_en` และ `description_en` สำหรับฝั่ง Guest / Member
- เพิ่ม Migration `add_translation_fields_to_products_and_categories` เพื่อรองรับข้อมูลภาษาอังกฤษของสินค้าและหมวดหมู่
- ปรับ Footer / ที่อยู่ / ข้อมูลติดต่อให้รองรับภาษาไทยและอังกฤษครบขึ้น
- แก้ระบบแสดงรูปสินค้าให้ใช้ URL กลางจาก Model (`ProductImage::url`) รองรับทั้งรูปที่มาจาก `public/images/...` และรูปที่อัปโหลดเข้า `storage/app/public/products`
- แก้หน้า Admin Product List ให้แสดงรูปสินค้าได้ถูกต้องทุกแหล่งที่มา
- แก้หน้า Product Detail / Home / Products ให้แสดงรูปสินค้าได้ถูกต้องทั้ง Guest และ Member
- ปรับหน้าเพิ่ม/แก้ไขสินค้า: เมื่อบันทึกแล้วให้อยู่หน้าแก้ไขเดิม ไม่เด้งกลับไปรายการสินค้า
- เพิ่ม Preview รูปภาพทันทีหลังเลือกไฟล์ก่อนกดบันทึก
- เพิ่มปุ่ม `ตั้งรูปหลัก` และ `ลบ` สำหรับจัดการรูปภาพสินค้าเดิม
- เพิ่มตัวเลือก `ลบรูปเดิมทั้งหมด แล้วใช้รูปที่อัปโหลดใหม่แทน` เพื่อป้องกันรูปซ้ำเมื่อแก้ไขสินค้า
- ปรับหน้า Admin Category เพิ่มปุ่มแก้ไข/บันทึก/ลบหมวดหมู่ และรองรับชื่อหมวดหมู่ภาษาอังกฤษ
- แก้ `routes/console.php` ให้ถูกต้อง เพื่อป้องกัน `syntax error, unexpected end of file` ตอนรัน Artisan

### หมายเหตุหลังอัปเดต v20
หลังแตกไฟล์ใหม่ ให้รันคำสั่งต่อไปนี้:

```powershell
composer install
php artisan migrate
php artisan route:clear
php artisan view:clear
php artisan config:clear
php artisan storage:link
```

ถ้า `storage:link` แจ้งว่า link already exists แต่รูปยังไม่ขึ้น ให้รัน:

```powershell
php artisan storage:unlink
php artisan storage:link
```

หรือบน Windows PowerShell:

```powershell
Remove-Item public\storage -Force -Recurse
php artisan storage:link
```

## อัปเดต v21
- ปรับระบบ SKU ให้สามารถเว้นว่างได้ หากแอดมินไม่กรอก ระบบจะสร้างให้อัตโนมัติและตรวจสอบไม่ให้ซ้ำ
- รูปแบบ SKU อัตโนมัติอ่านง่ายตามหมวดหมู่ เช่น `ST-FRESH-001`, `ST-DRIED-001`, `ST-JUICE-001`, `ST-JAM-001`, `ST-SNACK-001`, `ST-GIFT-001`
- เพิ่ม Migration `standardize_product_skus` สำหรับปรับ SKU ทดสอบเดิมที่เป็นเลข/รหัสสุ่ม 8 ตัว ให้เป็นรูปแบบอ่านง่าย
- หน้าเพิ่ม/แก้ไขสินค้า เอาข้อความ “บันทึกแล้วจะกลับมาหน้านี้...” ออก และเพิ่มปุ่มกลับรายการสินค้าในแถบล่างเพื่อให้กลับไปแก้สินค้าตัวอื่นได้สะดวก
- หน้าตะกร้าสินค้าเพิ่มรูปตัวอย่างสินค้าข้างชื่อสินค้า
- ชี้แจงระบบรูปสินค้า: ฐานข้อมูลเก็บเฉพาะ path ของรูปในตาราง `product_images` ส่วนไฟล์รูปจริงอยู่ที่ `storage/app/public/products` และแสดงผ่าน `public/storage` จากคำสั่ง `php artisan storage:link`

### หมายเหตุเรื่อง Database และรูปสินค้า
โปรเจกต์ Laravel นี้ไม่ได้แนบไฟล์ฐานข้อมูล MySQL จริงเป็น `.sql` มาด้วยตามปกติของ Laravel แต่มี `migrations` และ `seeders` สำหรับสร้างตารางและข้อมูลทดสอบใหม่ได้ด้วยคำสั่ง:

```powershell
php artisan migrate --seed
```

เมื่ออัปโหลดรูปสินค้า ระบบจะบันทึกข้อมูลลงฐานข้อมูลแบบนี้:

- ตาราง `product_images` เก็บ `product_id`, `path`, `is_primary`
- ไฟล์รูปจริงเก็บที่ `storage/app/public/products`
- หน้าเว็บแสดงรูปผ่าน URL `storage/products/...`

ถ้าย้ายโปรเจกต์ไปเครื่องอื่นและต้องการให้รูปที่อัปโหลดไว้ยังอยู่ ต้องคัดลอกโฟลเดอร์นี้ไปด้วย:

```text
storage/app/public/products
```

## อัปเดต v22
- แก้ระบบรูปสินค้าให้ยึดข้อมูลจากตาราง `product_images` เป็นหลัก
- รูปที่แอดมินอัปโหลดใหม่จะถูกเก็บไฟล์ไว้ที่ `storage/app/public/products` และเก็บ path ไว้ใน Database
- ถ้าสินค้ามีรูปที่อัปโหลดจริง ระบบจะแสดงรูปนั้นก่อนรูปตัวอย่างจาก Seeder ทุกหน้า เช่น หน้าร้าน, ตะกร้า, รายการสินค้าแอดมิน และหน้าแก้ไขสินค้า
- อัปโหลดรูปใหม่แล้วรูปแรกที่เลือกจะถูกตั้งเป็นรูปหลักอัตโนมัติ
- เพิ่ม Migration เพื่อปรับรูปที่เคยอัปโหลดไว้แล้วให้เป็นรูปหลักแทนรูปตัวอย่าง
- ย้ำ: ฐานข้อมูลจริงไม่ได้อยู่ในไฟล์ ZIP หากใช้ MySQL/XAMPP ข้อมูลจะอยู่ใน MySQL ของเครื่อง ต้อง Export ผ่าน phpMyAdmin หากต้องการย้ายข้อมูลพร้อมรูปที่เคยอัปโหลด

### รูปสินค้าเก็บตรงไหน
- ไฟล์รูป: `storage/app/public/products`
- path รูปในฐานข้อมูล: ตาราง `product_images` คอลัมน์ `path`
- การแสดงผลหน้าเว็บ: `asset('storage/'.$path)`

หลังอัปเดต v22 ให้รัน:

```bash
php artisan migrate
php artisan storage:link
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

ถ้า `php artisan storage:link` แจ้งว่า link already exists ไม่เป็นไร แปลว่ามีลิงก์อยู่แล้ว ถ้ารูปยังไม่ขึ้นให้รัน:

```bash
php artisan storage:unlink
php artisan storage:link
```

## อัปเดต v22-01
- แก้สถานะการชำระเงินฝั่ง Member ให้ `approved` และ `paid` เป็นสีเขียว
- แปลสถานะคำสั่งซื้อและสถานะชำระเงินตามภาษา TH/EN ในฝั่ง Guest/Member
- แก้กรณีสต๊อกสินค้าไม่พอ ไม่ให้แสดงหน้า Error Laravel 422 แล้ว เปลี่ยนเป็นหน้าขออภัยพร้อมปุ่มกลับตะกร้า/เลือกสินค้าใหม่
- ปรับ Checkout ให้ตรวจสอบสต๊อกก่อนสร้างคำสั่งซื้อและก่อนตัดสต๊อก

## อัปเดต v22-02
- ปรับสีธีมหลังบ้านจากแดงสดเป็นเขียวเข้ม/เขียวหม่น เพื่อลดอาการแสบตาเมื่อใช้งานนาน
- เพิ่ม Dashboard Analytics: ยอดขายวันนี้, ยอดขายเดือนนี้, ลูกค้าใหม่เดือนนี้, สลิปรอตรวจสอบ, แจ้งเตือนที่ต้องจัดการ, ออเดอร์ล่าสุด, สินค้าใกล้หมด และสินค้าขายดี Top 5
- เพิ่มกราฟยอดขาย 7 วันล่าสุด และปรับกราฟรายเดือน/รายปีให้อ่านง่ายขึ้น
- ปรับหน้ารายงานให้มีตัวกรองช่วงเวลา, รายงานยอดขาย, รายงานสินค้าขายดี, รายงานสต๊อก, รายงานลูกค้า, รายงานการชำระเงิน, รายงานสถานะออเดอร์ และรายงานใบเสร็จ
- เพิ่มปุ่มพิมพ์รายงานและ Export CSV เบื้องต้น
- เพิ่มระบบใบเสร็จรับเงินมาตรฐานไทย มีเลขที่ใบเสร็จ, เลขออเดอร์, วันที่, ข้อมูลลูกค้า, รายการสินค้า, SKU, ค่าจัดส่ง, ยอดสุทธิ และช่องลงชื่อ
- ฝั่ง Member สามารถดู/ดาวน์โหลดใบเสร็จได้เมื่อคำสั่งซื้อชำระเงินแล้ว
- ฝั่ง Admin สามารถเปิดใบเสร็จจากหน้ารายละเอียดออเดอร์และหน้าตรวจสอบชำระเงิน
- ปรับหน้าโอนเงินธนาคารให้มีการ์ดธนาคารกสิกรไทยแบบเห็นชัด มีไอคอน K, เลขบัญชี, ชื่อบัญชี และยอดที่ต้องโอน

หลังอัปเดต v22-02 ให้รัน:

```bash
composer install
php artisan migrate
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

## อัปเดต v22-03
- เพิ่มหน้า `ปรับแต่งธีม` สำหรับ Admin/Super Admin เพื่อเปลี่ยนสีแถบหลังบ้านได้เอง
- เพิ่มเมนู `ปรับแต่งธีม` ใน Sidebar หลังบ้าน
- สามารถตั้งค่าสี Sidebar ด้านบน/ด้านล่าง, สีปุ่มหลัก, สีพื้นหลัง, สีการ์ด และสีตัวอักษรหลัก
- เพิ่มตัวอย่าง Preview แถบเมนูแบบสดก่อนบันทึก
- เพิ่มธีมสำเร็จรูป เช่น เขียวหม่น, Blue Gray, Dark Dashboard และ Strawberry Soft
- เพิ่มปุ่มรีเซ็ตธีมกลับค่าเริ่มต้น
- บันทึกค่าสีไว้ในตาราง `settings` ของฐานข้อมูล

หลังอัปเดต v22-03 ให้รัน:

```bash
php artisan migrate
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

## อัปเดต v22-04
- เพิ่มการปรับแต่งสีฝั่ง Guest / Member เข้าไปในเมนู `ปรับแต่งธีม` ของ Admin
- แยกหมวดปรับสีเป็น 2 ส่วนชัดเจน: `Admin / Super Admin` และ `Guest / Member`
- ฝั่ง Guest / Member สามารถปรับสี Navbar, ปุ่ม, ตะกร้า, การ์ดสินค้า, พื้นหลัง, Footer และแถบข้อมูลได้
- เพิ่ม Preview แยกเป็นแท็บ `หลังบ้าน` และ `หน้าร้าน` เพื่อดูผลก่อนบันทึก
- เพิ่มธีมสำเร็จรูปที่เปลี่ยนทั้งหลังบ้านและหน้าร้านพร้อมกัน
- เปลี่ยนข้อความหัว Sidebar จาก `SB Admin 2` เป็น `SB Admin`
- ตั้งค่าเริ่มต้นฝั่ง Admin เป็นโทนเขียวหม่นสบายตา และฝั่งหน้าร้านเป็นโทนแดงสตรอว์เบอร์รี่/เขียวฟาร์ม
- บันทึกค่าสีทั้งหมดไว้ในตาราง `settings`

หลังอัปเดต v22-04 ให้รัน:

```bash
php artisan migrate
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

## อัปเดต v22-05
- แก้หน้าใบเสร็จที่เกิด Error `User::hasAnyRole()` โดยตรวจสิทธิ์จากฟิลด์ `role` แทน
- ตั้งค่าสีเริ่มต้นของธีม Admin เป็นชุดสีที่เลือกในหน้า Theme Customizer
  - Sidebar ด้านบน `#2461F0`
  - Sidebar ด้านล่าง `#EFA9F4`
  - สีปุ่มหลัก `#D94B5B`
  - สีพื้นหลัง `#F5F7F6`
  - สีการ์ด `#FFFFFF`
  - สีตัวอักษร `#13231F`
- เพิ่ม Favicon รูปสตรอเบอร์รี่ โดยเก็บไฟล์ไว้ที่ `public/favicon.png` และบันทึก path ในตาราง `settings` ด้วย key `site_favicon`
- เพิ่ม `<link rel="icon">` ใน Layout ฝั่งหน้าร้านและหลังบ้าน

## อัปเดต v22-06
- ปรับใบเสร็จรับเงินให้มีข้อมูลร้านค้า เลขประจำตัวผู้เสียภาษี Email เบอร์โทร และที่อยู่จากฐานข้อมูล `settings`
- เพิ่มส่วนลายเซ็น 2 ช่อง โดยแต่ละช่องมีพื้นที่สำหรับลายเซ็น/ตราประทับด้านบน และช่องลงชื่อด้านล่าง
- เพิ่มเมนูหลังบ้าน `ตั้งค่าข้อมูลร้านค้า` สำหรับแก้ชื่อกิจการ ที่ตั้ง Email เบอร์โทร Google Maps และเลขประจำตัวผู้เสียภาษี
- เพิ่มตัวเลือกในหน้า Checkout ให้ลูกค้าเลือกว่าต้องการใบกำกับภาษีหรือไม่
- ถ้าลูกค้าต้องการใบกำกับภาษี ระบบจะให้กรอกเลขประจำตัวผู้เสียภาษี ชื่อผู้เสียภาษี และที่อยู่สำหรับออกใบเสร็จ/ใบกำกับภาษี
- ข้อมูลภาษีของลูกค้าจะถูกบันทึกลงตาราง `orders` และนำไปแสดงบนใบเสร็จ

### หลังอัปเดต v22-06 ให้รัน
```powershell
php artisan migrate
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

## อัปเดต v22-07
- เพิ่มช่องปรับ `สีตัวอักษร Navbar/โลโก้หน้าร้าน` ในเมนูปรับแต่งธีม เพื่อแก้สีข้อความที่อยู่บนแถบเมนูหน้าร้าน
- ตั้งค่าธีมหน้าร้านเริ่มต้นเป็นชุดสีใหม่ตามที่เลือก และเก็บธีมเดิมของหน้าร้าน/แอดมินไว้ในธีมสำเร็จรูป
- เพิ่มการคำนวณ VAT 7% ในหน้า Checkout และบันทึกยอดก่อน VAT, VAT, ภาษีหัก ณ ที่จ่าย และหมายเหตุค่าจัดส่งลงคำสั่งซื้อ
- เพิ่มกฎค่าจัดส่ง: เชียงใหม่ 50 บาท, ต่างจังหวัด 100 บาท, หากจำนวนสินค้าในตะกร้ามากกว่า 10 ชิ้น เพิ่มอีก 50 บาท
- ปรับ Layout ใบเสร็จรับเงิน/ใบกำกับภาษีเป็นหน้า A4 ใกล้เคียงเอกสารมาตรฐานไทย มีโลโก้, ข้อมูลผู้ขาย/ลูกค้า, เลขที่เอกสาร, วันที่ออกเอกสาร, ตารางสินค้า, VAT, หัก ณ ที่จ่าย และช่องรับรองเอกสาร
- เพิ่มโลโก้ PNG พื้นหลังโปร่งใสสำหรับใช้ในใบเสร็จ โดยเก็บไว้ที่ `public/images/receipt-logo.png`

### หลังอัปเดต v22-07 ให้รัน
```powershell
php artisan migrate
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

## อัปเดต v22-08
- เพิ่มโลโก้ภาษาไทยและภาษาอังกฤษแบบ PNG พื้นหลังโปร่งใส
- ชื่อภาษาอังกฤษใช้ `Rai Khaisaeng Strawberry` และ `Community Enterprise`
- Navbar หน้าร้านจะสลับโลโก้ตามภาษา TH/EN อัตโนมัติ
- ใบเสร็จ/ใบกำกับภาษีเลือกโลโก้ตามภาษาได้
- เพิ่มช่องอัปโหลดโลโก้ภาษาไทย/อังกฤษ และ Favicon ในหน้า `ตั้งค่าข้อมูลร้านค้า`
- ตั้งค่า Favicon เป็นรูปสตรอเบอร์รี่ใหม่

หลังอัปเดตให้รัน:

```bash
php artisan migrate
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

## อัปเดต v22-09
- ปรับ Layout ใบเสร็จ/ใบกำกับภาษี A4 ให้กระชับขึ้น โดยเฉพาะส่วนสรุปยอดเงิน
- ปรับส่วนลายเซ็นกลับเป็น 2 ช่องหลัก: ผู้ขาย และ ลูกค้า พร้อมช่องลายเซ็น/ตราประทับ
- ตั้งค่าสีเริ่มต้นฝั่ง Guest/Member กลับเป็นธีมเดิมแนว v20-14: แดง/ชมพูอ่อน/เขียว/พื้นหลังขาว
- เพิ่ม/คงธีมสำเร็จรูปไว้ให้เลือกทั้งธีมเดิมและธีมใหม่ เพื่อย้อนกลับได้ง่าย

## อัปเดต v22-10
- ปรับหน้า Checkout ให้ข้อความภาษาไทยครบขึ้นในส่วนข้อมูลใบกำกับภาษี วิธีชำระเงิน และสรุป VAT
- จำกัดเลขประจำตัวผู้เสียภาษีลูกค้าให้กรอกได้เฉพาะตัวเลข 13 หลัก และตรวจสอบฝั่ง Backend ด้วย `digits:13`
- เพิ่มกล่องแสดง Validation Error ในหน้า Checkout เพื่อให้ลูกค้ารู้ว่ากรอกข้อมูลส่วนไหนไม่ครบ แทนที่จะเหมือนกดยืนยันแล้วอยู่หน้าเดิม
- ปรับใบเสร็จ/ใบกำกับภาษี: เอาข้อความซ้ำใต้โลโก้ออก, ย้ายหัวข้อเอกสารไปด้านขวา, ลดขนาดกล่องสรุปยอด และคงช่องลายเซ็นผู้ขาย/ลูกค้าแบบ 2 ช่อง
- เพิ่ม Migration ตั้งค่าสีเริ่มต้น Admin ตามชุดสีที่กำหนด: `#2461F0`, `#EFA9F4`, `#D94B5B`, `#F5F7F6`, `#FFFFFF`, `#13231F`
- คงสีฝั่ง Guest/Member แบบ v20-14 เป็นค่าเริ่มต้น และยังมีธีมสำเร็จรูปให้เลือกย้อนกลับได้

หลังอัปเดตให้รัน:

```bash
php artisan migrate
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

## อัปเดต v22-11
- ปรับ Layout ใบเสร็จ/ใบกำกับภาษี: ข้อมูลร้านอยู่ใต้โลโก้, หัวข้ออยู่บรรทัดเดียว, ลายเซ็นเหลือผู้ขาย/ลูกค้าแบบไม่มีกรอบข้อความ
- หน้า Checkout จำกัดช่องเบอร์โทรให้กรอกได้เฉพาะตัวเลข 9-10 หลัก
- หน้า Checkout จำกัดเลขประจำตัวผู้เสียภาษี 13 หลัก
- เพิ่มการแจ้งเตือนจำนวนออเดอร์ใหม่ที่เมนูออเดอร์ฝั่ง Admin
- เพิ่มการแจ้งเตือนจำนวนการชำระเงินรอตรวจสอบที่เมนูชำระเงินฝั่ง Admin
- Footer หน้าร้านปรับสีไอคอน Facebook / YouTube / LINE ให้เป็นสีตามแบรนด์จริง
- เพิ่มช่องแก้ไขลิงก์ Facebook / YouTube / LINE ในเมนูตั้งค่าข้อมูลร้านค้า

## อัปเดต v22-12
- ปรับ Layout ใบเสร็จ/ใบกำกับภาษีใหม่ตามตัวอย่างที่อนุมัติ: ข้อมูลร้านอยู่ใต้โลโก้แบบไม่ตัดบรรทัดง่าย, หัวข้ออยู่ขวา, ข้อมูลเอกสารอยู่ใต้หัวข้อ
- ลดขนาดหัวข้อใบเสร็จให้เหมาะกับ A4
- แก้สี Social icon ฝั่ง Guest/Member ให้แสดงสีแบรนด์จริง
- แปลหน้า Checkout เพิ่มเติมเมื่อเลือกภาษาอังกฤษ
- แปลส่วน VAT / Withholding Tax / Tax Invoice Information ในหน้า Checkout

## v22-14: Validation + Edge Case + Free Hosting Guide
- เพิ่ม Validation สำคัญใน Register/Login, Profile, Address, Cart, Checkout, Product, User, Payment
- เบอร์โทรจำกัดตัวเลข 9–10 หลัก
- รหัสไปรษณีย์จำกัดตัวเลข 5 หลัก
- เลขผู้เสียภาษีจำกัด 13 หลัก
- Upload Slip รองรับ `jpg`, `jpeg`, `png` และจำกัดขนาด 5MB
- ป้องกัน Cart ใส่จำนวนติดลบหรือมากเกินไป
- ป้องกันเพิ่มสินค้าลงตะกร้ามากกว่าสต๊อกจริง
- Checkout ตรวจสต๊อกซ้ำใน Database Transaction พร้อม `lockForUpdate()` ป้องกันตัดสต๊อกชนกัน
- ถ้าสต๊อกไม่พอ แสดงหน้าขออภัยให้ลูกค้าเลือกสินค้าใหม่ ไม่โชว์ Error Laravel
- ป้องกันกดอนุมัติ/ปฏิเสธการชำระเงินซ้ำ
- เพิ่ม Soft Delete ให้ Product/User เพื่อไม่กระทบออเดอร์และใบเสร็จเก่า
- เพิ่มคำสั่ง `php artisan orders:expire` สำหรับยกเลิกออเดอร์รอชำระที่หมดอายุ
- เพิ่มคู่มือ Deploy บน Hosting ฟรีไว้ใน README_TH


## v22-15: Order Number + Address Database Validation + Extra Edge Case
- ป้องกันเลขออเดอร์ซ้ำ 100% โดยสร้างเลขคำสั่งซื้อด้วยเวลา + Random 6 ตัวอักษร และยังคงมี Unique Index ใน Database
- Checkout ใช้ Transaction และ `lockForUpdate()` กับตะกร้าและสต๊อก เพื่อป้องกันการกดสั่งซื้อซ้ำ/หลายคนซื้อสินค้าพร้อมกัน
- เพิ่ม Validation จังหวัด → อำเภอ/เขต → ตำบล/แขวง → รหัสไปรษณีย์ จาก Database จริง (`thai_provinces`, `thai_districts`, `thai_subdistricts`)
- ถ้าลูกค้าส่งค่า Address ปลอมผ่าน Browser โดยไม่เลือกจาก Dropdown ระบบจะไม่ให้ผ่าน
- ตรวจว่ารหัสไปรษณีย์ตรงกับตำบล/แขวงที่เลือก
- ใบเสร็จยังคงป้องกัน User เปิดใบเสร็จของคนอื่น แต่ Admin / Super Admin สามารถเปิดดูได้
- จำกัดสลิปเป็นไฟล์รูปภาพ `jpg`, `jpeg`, `png` เท่านั้น เพื่อป้องกันไฟล์ผิดชนิดและปัญหาการแสดงภาพในหลังบ้าน
- เพิ่มข้อจำกัดช่องเบอร์โทรในหน้า Profile/Address ให้กรอกเฉพาะตัวเลข 9–10 หลัก

หลังอัปเดตให้รัน:

```powershell
php artisan migrate
php artisan route:clear
php artisan view:clear
php artisan config:clear
```


## v22-16: RBAC + Test Accounts + Social Login Guide
- ปรับสิทธิ์ RBAC ให้ชัดเจน: Admin ใช้งานฟังก์ชันปฏิบัติงานประจำวัน ส่วน Super Admin เท่านั้นที่จัดการผู้ใช้งาน สิทธิ์ ธีม และตั้งค่าระบบหลักได้
- เมนูหลังบ้านซ่อนเมนูที่เป็นของ Super Admin จาก Admin ทั่วไป เช่น ปรับแต่งธีม ตั้งค่าข้อมูลร้านค้า และจัดการผู้ใช้งาน
- เพิ่มบัญชีทดสอบ `user_test@khaisaeng.test`, `admin_test@khaisaeng.test`, `sbadmin_test@khaisaeng.test` ใน Seeder
- เพิ่มคำอธิบายบัญชีทดสอบในหน้า Login เพื่อให้ผู้ตรวจระบบเลือกทดสอบแต่ละ Role ได้ง่าย
- หน้า Register เพิ่มปุ่มสมัคร/เข้าสู่ระบบด้วย Google, Facebook และ LINE เช่นเดียวกับหน้า Login
- ปรับสิทธิ์ใบเสร็จ: Member ดูได้เฉพาะออเดอร์ตัวเอง ส่วน Admin และ Super Admin ดูใบเสร็จของทุกออเดอร์ได้
- อัปเดต README_TH ให้มีบัญชีทดสอบ ขั้นตอน Social Login และคำอธิบายสิทธิ์ Admin/Super Admin

หลังอัปเดตให้รัน:

```powershell
php artisan migrate --seed
php artisan route:clear
php artisan view:clear
php artisan config:clear
```

## อัปเดต v22-18: ระบบตรวจสลิป 3 ระดับ

ปรับระบบตรวจสอบสลิปให้ทำงานเป็นลำดับดังนี้

1. **Level 1: QR Code Detector**
   - ตรวจรูปแบบ QR Code ในรูปสลิปก่อน
   - ถ้าพบ QR Code ระบบจะแสดงสถานะ `ผ่าน QR`
   - ยังให้แอดมินตรวจยืนยันขั้นสุดท้ายก่อนอนุมัติเงิน

2. **Level 2: OCR ด้วย Tesseract**
   - ถ้าไม่พบ QR Code ระบบจะอ่านตัวอักษรด้วย Tesseract OCR
   - ตรวจคำสำคัญ เช่น ธนาคาร, โอนเงิน, ยอดเงิน, เลขอ้างอิง, PromptPay, Transfer, Amount
   - คะแนนตั้งแต่ 3 ขึ้นไปจะแสดง `ผ่าน OCR`
   - คะแนน 1-2 จะแสดง `ต้องตรวจสอบ`
   - ถ้า OCR อ่านไม่ได้หรือไม่พบข้อมูลที่คล้ายสลิป ระบบจะปฏิเสธรูปนั้น

3. **Level 3: Admin ตรวจเอง**
   - รายการที่ระบบยังไม่มั่นใจจะถูกส่งให้ Admin / Super Admin ตรวจในหน้า `ชำระเงิน`
   - Admin ยังเป็นผู้กดยืนยันหรือปฏิเสธการชำระเงินขั้นสุดท้าย

### การตั้งค่า OCR บน Windows

ติดตั้ง Tesseract OCR แล้วเพิ่มใน `.env` ด้วยเครื่องหมาย `/` แทน `\` เพื่อป้องกัน dotenv error

```env
TESSERACT_PATH="C:/Program Files/Tesseract-OCR/tesseract.exe"
```

หลังแก้ `.env` ให้รัน

```bash
php artisan optimize:clear
```

### หมายเหตุ Composer

เวอร์ชัน LINE Provider ที่ใช้ได้กับโปรเจกต์นี้คือ

```json
"socialiteproviders/line": "^4.1"
```



---

## อัปเดต v22-22

เวอร์ชันนี้ใช้ฐานข้อมูลและระบบหลักจาก v22-18 และเพิ่มเฉพาะส่วนที่จำเป็นดังนี้

- เปลี่ยนฟอนต์ทั้งระบบเป็นสไตล์ Modern / Minimal
  - ภาษาไทย: Prompt, Kanit, Bai Jamjuree
  - ภาษาอังกฤษ: Inter, Roboto
- เคลียร์ธีมสำเร็จรูปชุดเก่าออกจากหน้า Admin Theme
- เพิ่มธีมสำเร็จรูปฝั่ง User / Guest / Member จำนวน 12 ธีมตามรายการใหม่
- ไม่เพิ่ม Cursor Effect
- ไม่เพิ่ม Motion Design
- เพิ่ม `package.json` แบบเบา ๆ เพื่อไม่ให้ `npm install` error หากมีคนลองรัน แต่ระบบไม่ได้บังคับใช้ npm

### หมายเหตุเรื่อง npm ใน v22-22

โปรเจกต์นี้โหลด Bootstrap, Bootstrap Icons และ Google Fonts ผ่าน CDN จึงสามารถรันระบบได้โดยไม่ต้องใช้ npm

หากต้องการลองรันคำสั่ง npm สามารถรันได้ แต่ไม่จำเป็น:

```powershell
npm install
npm run build
```

คำสั่งข้างบนจะไม่ build asset เพิ่ม เพราะระบบใช้ CDN เป็นหลัก

### คำสั่งรันหลักที่แนะนำ

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan optimize:clear
php artisan serve
```


---

## v22-22 Rai Khaisaeng Theme + Motion Design

### สิ่งที่เพิ่ม/ปรับ
- ปรับ Footer ฝั่ง Guest / Member เป็นสีเขียวเข้ม `#1F4F38` และสีเข้มเสริม `#183F2D`
- แถบที่อยู่เหนือ Footer ยังคงใช้สีข้อความ `#4A3E3D` ตาม Rai Khaisaeng Theme
- สีหลักอื่น ๆ ของหน้าร้านยังอิง Rai Khaisaeng Theme v22-22 เช่น `#FDFBF7`, `#E8A7A1`, `#C35B53`, `#4A3E3D`
- เพิ่ม Motion Design แบบเบา ๆ ไม่ใช้ Cursor Effect
- Motion ที่เพิ่ม: Fade-up ตอนเลื่อนหน้า, Hover ยกการ์ดสินค้า/ไอคอน, Admin card fade-up
- รองรับ `prefers-reduced-motion` เพื่อลด animation หากผู้ใช้ตั้งค่าลด motion ในเครื่อง

### หมายเหตุเรื่อง npm
โปรเจกต์นี้ยังใช้ CDN assets เป็นหลัก จึงไม่จำเป็นต้องรัน npm หากไม่ได้แก้ frontend build

ถ้าต้องการเช็กคำสั่ง npm สามารถรันได้ แต่จะเป็นคำสั่งแจ้งเตือนเท่านั้น:

```powershell
npm install
npm run build
```

คำสั่งหลักสำหรับรันระบบยังเหมือนเดิม:

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear
php artisan serve
```


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

---

## อัปเดต v24: Responsive Design / Mobile First

เวอร์ชันนี้เพิ่มการรองรับมือถือและแท็บเล็ตสำหรับฝั่ง Guest / Member / Admin / Super Admin โดยเน้นให้ใช้งานง่ายขึ้นบนหน้าจอเล็ก

### สิ่งที่ปรับสำหรับมือถือ

- Navbar หน้าร้านปรับเป็น Hamburger Menu
- โลโก้และเมนูย่อขนาดไม่ให้ล้นจอ
- ช่องค้นหาแสดงเต็มความกว้างบนมือถือ
- Hero Banner ลดความสูงและจัดข้อความให้อ่านง่าย
- Product Card กระชับขึ้น และแสดง 2 คอลัมน์บนมือถือ
- ตะกร้าสินค้าเปลี่ยนจากตารางเป็นรูปแบบอ่านง่ายบนมือถือ
- Checkout เรียงเป็นแนวตั้ง: ที่อยู่ → รายการสินค้า → ชำระเงิน
- ปุ่มสั่งซื้อบนมือถือเป็น Sticky ด้านล่าง เพื่อกดง่าย
- Footer ลดความสูงของแผนที่และจัดข้อมูลเป็นชั้น
- Admin Sidebar เปลี่ยนเป็นปุ่มเมนู Overlay บนมือถือ
- ตารางหลังบ้านใช้การเลื่อนแนวนอน ป้องกันข้อมูลแตก

### คำแนะนำการทดสอบ Responsive

ให้เปิด Chrome DevTools แล้วทดสอบขนาดหน้าจอ:

- iPhone SE / 375px
- iPhone 14 / 390px
- Samsung Galaxy / 412px
- iPad / 768px
- Laptop / 1366px

### สิ่งที่ควรตรวจบนมือถือ

- เมนูไม่ล้นจอ
- ปุ่มกดง่าย ขนาดไม่เล็กเกินไป
- ตะกร้าและ Checkout อ่านง่าย
- ตารางหลังบ้านไม่แตก
- รูปสินค้าไม่บิดเบี้ยว
- Footer ไม่ยาวหรือแน่นเกินไป
- ใบเสร็จเปิดดูได้และเลื่อนดูได้บนมือถือ

### คำสั่งรันเหมือนเดิม

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan optimize:clear
php artisan serve
```

ถ้ามีการแก้ CSS/JS ผ่าน Vite ให้รันเพิ่ม:

```bash
npm install
npm run build
```

---

## v25: Railway Deploy Ready

เวอร์ชันนี้เตรียมไว้สำหรับอัปขึ้น GitHub และ Deploy บน Railway โดยแก้ปัญหาที่เจอจาก v24 แล้ว:

- แก้ `View path not found` ตอน Railway รัน `php artisan view:cache`
- เพิ่ม `config/view.php`
- เพิ่ม PHP extensions ที่จำเป็นใน `composer.json`: `ext-gd`, `ext-zip`, `ext-pdo`, `ext-pdo_mysql`
- เพิ่ม `railway.toml` และ `nixpacks.toml`
- เพิ่ม `.gitignore` สำหรับ Laravel มาตรฐาน
- ไม่แนบไฟล์ `.env` จริงใน ZIP เพื่อความปลอดภัย ให้ใช้ `.env.example` แล้ว copy เป็น `.env`

### คำสั่งรันใน VS Code หลังแตกไฟล์ ZIP

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate:fresh --seed
php artisan optimize:clear
php artisan serve
```

### คำสั่งอัปขึ้น GitHub

```bash
git init
git add .
git commit -m "v25: railway deploy ready"
git branch -M main
git remote add origin https://github.com/Jhirawat/rai_khaisaeng_strawberry_demo.git
git push -u origin main --force
```

### Railway Variables ที่ควรใส่

```env
APP_NAME="Rai Khaisaeng Strawberry"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:ใส่ค่าจากคำสั่ง php artisan key:generate --show
APP_URL=https://โดเมนของ-railway.up.railway.app

DB_CONNECTION=mysql
DB_HOST=${MYSQLHOST}
DB_PORT=${MYSQLPORT}
DB_DATABASE=${MYSQLDATABASE}
DB_USERNAME=${MYSQLUSER}
DB_PASSWORD=${MYSQLPASSWORD}

FILESYSTEM_DISK=public
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

> หากยังไม่เพิ่ม MySQL บน Railway ให้ Deploy เว็บให้ Build ผ่านก่อน แล้วค่อยเพิ่ม Database และรัน migrate/seed ภายหลัง

### อัปเดต v26
หลังอัปเดตขึ้น Railway ให้ตั้ง Environment Variables ใน Railway แทนการอัปโหลด `.env` เช่น `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`, `FACEBOOK_*`, `LINE_*` แล้วรัน `php artisan migrate --seed` เพื่อสร้างตารางโปรโมชั่นและ seed ข้อมูลจังหวัด/อำเภอ/ตำบล

เมนูใหม่: Admin → โฆษณา / โปรโมชั่น ใช้เพิ่ม ลบ แก้ไขแบนเนอร์หน้าแรกได้

### อัปเดต v27
หลังอัปขึ้น Railway ให้รัน:
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

Social Login ต้องตั้งค่าใน Railway > Variables ไม่ใช่ใส่ไฟล์ `.env` ลง GitHub:
```env
APP_URL=https://raikhaisaengstrawberrydemo-production.up.railway.app
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=${APP_URL}/auth/google/callback
FACEBOOK_CLIENT_ID=
FACEBOOK_CLIENT_SECRET=
FACEBOOK_REDIRECT_URI=${APP_URL}/auth/facebook/callback
LINE_CLIENT_ID=
LINE_CLIENT_SECRET=
LINE_REDIRECT_URI=${APP_URL}/auth/line/callback
```

รูปหน้าแรกแก้ได้ที่ Admin > ตั้งค่าข้อมูลร้านค้า > โลโก้ / Favicon > รูปพื้นหลังข้อความหน้าแรก
รูปโปรโมชั่นแก้ได้ที่ Admin > โฆษณา / โปรโมชั่น > เพิ่ม/แก้ไข > รูปข้างโปรโมชั่น / รูปแบนเนอร์


### อัปเดต v27.1
- เข้าเมนู `Admin > โฆษณา / โปรโมชั่น` เพื่อแก้รูปข้างโปรโมชั่นหน้าแรก
- เลือกตำแหน่ง `หน้าแรก: รูปข้างโปรโมชั่น` แล้วอัปโหลดรูปใหม่ พร้อมแก้หัวข้อ รายละเอียด ข้อความปุ่ม และลิงก์ปุ่ม
- ปรับปุ่มหน้าร้านและหลังบ้านให้ข้อความอยู่กึ่งกลาง ไม่ติดขอบด้านบน
- Social Login ยังต้องตั้งค่า Railway Variables จริงก่อนใช้งาน: GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, FACEBOOK_CLIENT_ID, FACEBOOK_CLIENT_SECRET, LINE_CLIENT_ID, LINE_CLIENT_SECRET และ Redirect URI ของแต่ละ Provider


# v28 Production Ready Hardening

เพิ่มเมนู Admin ใหม่:
- Activity Log: ดูประวัติการแก้ไขสินค้า โปรโมชั่น ออเดอร์ และการชำระเงิน
- Backup / Export: Export CSV สำหรับสินค้า ออเดอร์ สมาชิก และคลังสินค้า
- Social Login Status: ตรวจ Railway Variables และ Callback URL ของ Google/Facebook/LINE

Security / Edge Case ที่เพิ่ม:
- Login Rate Limit 5 ครั้งต่อนาที
- Security Headers สำหรับ production
- Seed Protection กัน Demo Seeder ทับข้อมูลจริง
- ยกเลิกออเดอร์คืน Stock เพียงครั้งเดียว
- บันทึกผู้ตรวจสอบสลิป verified_by / verified_at

หลัง Deploy ให้รัน:
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

ถ้าเป็นข้อมูลจริง ให้ตั้ง `ALLOW_DEMO_SEED=false` เสมอ

---

# v29 Production Stable Release

เวอร์ชันนี้เป็นรุ่น Stable สำหรับ Deploy จริง โดยรวมฟีเจอร์จาก v28 และเพิ่ม Activity Log ให้ครอบคลุมมากขึ้น

## จุดเด่น v29
- Security Hardening: HTTPS, Security Headers, Login Rate Limit, APP_DEBUG=false ready
- Business Edge Cases: คืน Stock เมื่อยกเลิกออเดอร์เพียงครั้งเดียว, ป้องกัน Seed ทับข้อมูลจริง
- Admin Operations: Activity Log, Backup/Export CSV, Social Login Status
- Promotion Editor: แก้รูปข้างโปรโมชั่น, หัวข้อ, รายละเอียด, ปุ่ม, ลิงก์
- UX/UI: ปุ่มและ Badge ปรับระยะห่างดีขึ้น, Mobile Admin รองรับดีขึ้น

## Railway Variables สำคัญ
```env
APP_ENV=production
APP_DEBUG=false
ALLOW_DEMO_SEED=false
```

## คำสั่งหลัง Deploy
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

## หมายเหตุ Social Login
ให้ตรวจ Callback URL ที่เมนู `Admin > Social Login Status` แล้วนำไปใส่ใน Google/Facebook/LINE Developer Console ให้ตรงกัน


### v29.1 Quick Production Fix
- ปุ่มจำนวนสินค้าในหน้ารายละเอียดสินค้าใช้ UI เดียวกับหน้าตะกร้า
- แผนที่ Footer แสดงตำแหน่งพิกัด 18.854859, 98.561256
- เบอร์ติดต่อคุณตี๋: 089-265-5685
