# README_TH_VSCODE.md

# Rai Khaisaeng Strawberry Production Prototype v22-22
## คู่มือรันระบบใน VS Code สำหรับเพื่อนที่นำไฟล์ไปทดสอบ

เวอร์ชันนี้ใช้ฐานจาก v22-18 และเพิ่มฟอนต์ Modern / Minimal + ธีมสำเร็จรูปฝั่ง User ใหม่ 12 ธีม

สิ่งที่ไม่มีใน v22-22:
- ไม่มี Cursor Effect
- ไม่มี Motion Design
- ไม่บังคับใช้ npm / Vite

---

## 1. โปรแกรมที่ต้องติดตั้งก่อน

- XAMPP หรือ PHP 8.2+
- Composer
- MySQL
- Visual Studio Code
- Tesseract OCR สำหรับตรวจสลิป

Node.js / npm เป็นตัวเลือกเสริมเท่านั้น ไม่จำเป็นสำหรับการรันระบบนี้

---

## 2. ตรวจสอบ Tesseract OCR

```powershell
tesseract --version
```

ตรวจภาษาไทย:

```powershell
tesseract --list-langs | findstr tha
```

ต้องขึ้น:

```text
tha
```

ในไฟล์ `.env` ใช้แบบนี้:

```env
TESSERACT_PATH="C:/Program Files/Tesseract-OCR/tesseract.exe"
```

---

## 3. เปิดโปรเจกต์ใน VS Code

แตก ZIP แล้วเปิดโฟลเดอร์หลักของโปรเจกต์ใน VS Code

ต้องเห็นไฟล์เหล่านี้:

```text
artisan
composer.json
.env.example
app
resources
routes
database
```

---

## 4. เปิด XAMPP

Start:

- Apache
- MySQL

ระบบเว็บที่รันในเครื่องใช้ฐานข้อมูล MySQL จาก XAMPP ตามค่าใน `.env` ดังนั้นต้องเปิด MySQL ก่อนรัน migration, seeder หรือเปิดหน้าร้าน ส่วน Apache ไม่จำเป็นเมื่อใช้ `artisan serve`

---

## 5. คำสั่งติดตั้งและรันครั้งแรก

```powershell
composer install
copy .env.example .env
C:\xampp\php\php.exe artisan key:generate
C:\xampp\php\php.exe artisan migrate:fresh --seed
C:\xampp\php\php.exe artisan storage:link
C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

เปิดเว็บ:

```text
http://127.0.0.1:8000
```

---

## 6. หากต้องการลอง npm

เวอร์ชันนี้มี `package.json` ให้แล้วเพื่อป้องกัน npm error แต่ระบบไม่ได้บังคับใช้ npm

```powershell
npm install
npm run build
```

ถ้าไม่รัน npm ระบบก็ยังใช้งานได้ เพราะใช้ CDN สำหรับ Font และ UI หลัก

---

## 7. สิ่งที่เพิ่มใน v22-22

- ฟอนต์ Prompt / Kanit / Bai Jamjuree / Inter / Roboto
- ธีมสำเร็จรูปฝั่ง User จำนวน 12 ธีม
- ค่าเริ่มต้นธีม User เป็น Strawberry & Cream
- ใช้ข้อมูลและ Logic หลักจาก v22-18
- ไม่มี Cursor Effect และไม่มี Motion Design

---

## 8. บัญชีทดสอบ

User:

```text
user_test@khaisaeng.test
password
```

Admin:

```text
admin_test@khaisaeng.test
password
```

Super Admin:

```text
sbadmin_test@khaisaeng.test
password
```

---

## 9. แก้ปัญหาพื้นฐาน

ถ้าแก้ `.env` แล้วระบบยังไม่เปลี่ยน:

```powershell
C:\xampp\php\php.exe artisan optimize:clear
```

ถ้า storage link มีอยู่แล้ว:

```text
The public/storage link already exists.
```

ไม่ต้องตกใจ ใช้งานต่อได้

---

## 10. งานประจำ ระบบทดสอบ และการย้ายสลิปแบบปลอดภัย

### รันตัวตั้งเวลาของ Laravel

เปิด Terminal อีกหน้าหนึ่งค้างไว้ระหว่างที่ระบบทำงาน:

```powershell
C:\xampp\php\php.exe artisan schedule:work
```

คำสั่งนี้เรียกงานตามเวลา เช่น การหมดอายุของคำสั่งซื้อที่รอชำระเงิน ส่วนเว็บให้รันแยกอีก Terminal ด้วย:

```powershell
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

### รัน automated tests

`phpunit.xml` บังคับให้ automated tests ใช้ SQLite แบบ `:memory:` ชั่วคราว ไม่แตะ MySQL ใน `.env` และไม่แตะข้อมูลร้านจริง:

```powershell
C:\xampp\php\php.exe artisan test
```

ตรวจ extension ที่ XAMPP โหลดอยู่ก่อนด้วย `C:\xampp\php\php.exe -m` ถ้าเห็น `pdo_sqlite` แล้ว ไม่ต้องเติม `-d extension=pdo_sqlite` ซ้ำ เพราะ PHP จะแจ้งเตือนว่าโหลด extension ซ้ำ สำหรับเครื่องที่ยังไม่เปิด extension ที่จำเป็น ให้เปิด `pdo_sqlite`, `sqlite3`, `gd` และ `zip` เพียงครั้งเดียวใน `C:\xampp\php\php.ini` แล้วปิด/เปิด Terminal ใหม่ หรือใช้คำสั่งชั่วคราวนี้เฉพาะ extension ที่ยังไม่แสดงใน `-m`:

```powershell
C:\xampp\php\php.exe -d extension=pdo_sqlite -d extension=sqlite3 -d extension=gd -d extension=zip artisan test
```

ห้ามเปลี่ยน test database ไปเป็น MySQL เพื่อแก้ปัญหา test เพราะ test harness จะหยุดทำงานทันทีหากไม่ได้ใช้ SQLite `:memory:`

### ย้าย payment slip จาก public storage ไป private storage

1. เข้า Admin > Backup / Export และเก็บไฟล์สำรองก่อนทุกครั้ง
2. รัน dry-run เพื่อตรวจรายการ โดยยังไม่ย้ายหรือลบไฟล์:

```powershell
C:\xampp\php\php.exe artisan payments:migrate-slips-private --dry-run
```

3. ตรวจผลลัพธ์และจำนวน failed ให้เป็น 0 แล้วจึงรันจริง:

```powershell
C:\xampp\php\php.exe artisan payments:migrate-slips-private
```

หากมี failed ห้ามลบไฟล์สลิปต้นทางเอง ให้ตรวจ backup และสาเหตุก่อนรันซ้ำ

### รักษาความลับของ `.env`

- ใช้ `.env.example` เป็นแม่แบบ แต่ห้าม commit, push, แนบไฟล์ หรือส่ง `.env` ให้ผู้อื่น
- `.env` อาจมีรหัสผ่านฐานข้อมูล, APP_KEY, social-login secret และค่าลับของบริการภายนอก
- เมื่อแก้ `APP_ENV` หรือค่า config ให้รัน `C:\xampp\php\php.exe artisan optimize:clear` ก่อนตรวจผล เพื่อไม่ให้ config cache เก่าค้างอยู่


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
C:\xampp\php\php.exe artisan key:generate
C:\xampp\php\php.exe artisan migrate --seed
C:\xampp\php\php.exe artisan storage:link
C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
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

```powershell
C:\xampp\php\php.exe artisan migrate:fresh --seed
C:\xampp\php\php.exe artisan storage:link
C:\xampp\php\php.exe artisan optimize:clear
```

### ไฟล์สำคัญ

```text
database/seeders/DemoProductionSeeder.php
database/sql/README_DEMO_DATABASE_TH.md
public/images/products/demo-product-*.svg
storage/app/public/slips/demo-slip-*.svg
```

---

## v24 Responsive Design สำหรับทดสอบใน VS Code

หลังรันเว็บแล้ว ให้ทดสอบ Mobile ด้วย Chrome DevTools:

1. เปิดเว็บ `http://127.0.0.1:8000`
2. กด `F12`
3. กดไอคอนมือถือ/แท็บเล็ต หรือกด `Ctrl + Shift + M`
4. เลือกขนาดหน้าจอ เช่น iPhone SE, iPhone 14, Galaxy, iPad

### จุดที่ต้องเทสบนมือถือ

- หน้าแรก / Hero Banner
- รายการสินค้า / Product Grid
- รายละเอียดสินค้า
- ตะกร้าสินค้า
- Checkout
- ประวัติคำสั่งซื้อ
- ใบเสร็จ
- Login / Register
- Admin Dashboard
- Admin Orders / Payments / Products / Inventory

### คำสั่งรันสำหรับเทส v24 ใน VS Code

```powershell
composer install
copy .env.example .env
C:\xampp\php\php.exe artisan key:generate
C:\xampp\php\php.exe artisan migrate:fresh --seed
C:\xampp\php\php.exe artisan storage:link
C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

ถ้าต้องการ build asset frontend:

```powershell
npm install
npm run build
```

---

## v25: คำสั่งสำหรับ VS Code + GitHub + Railway

### รัน Local

```powershell
composer install
copy .env.example .env
C:\xampp\php\php.exe artisan key:generate
C:\xampp\php\php.exe artisan storage:link
C:\xampp\php\php.exe artisan migrate:fresh --seed
C:\xampp\php\php.exe artisan optimize:clear
C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000
```

### ตรวจสอบก่อน Push

```powershell
git status
```

ไม่ควรเห็น `.env`, `vendor/`, `node_modules/` เพราะถูก ignore แล้ว

### Push ไป GitHub

```powershell
git add .
git commit -m "v25: railway deploy ready"
git push -u origin main --force
```

### ถ้าต้องสร้าง APP_KEY สำหรับ Railway

```powershell
C:\xampp\php\php.exe artisan key:generate --show
```

นำค่าที่ได้ไปใส่ใน Railway Variable: `APP_KEY`
