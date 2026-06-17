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

---

## 5. คำสั่งติดตั้งและรันครั้งแรก

```powershell
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan optimize:clear
php artisan serve
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
user_test@example.com
password
```

Admin:

```text
admin_test@example.com
password
```

Super Admin:

```text
sbadmin_test@example.com
password
```

---

## 9. แก้ปัญหาพื้นฐาน

ถ้าแก้ `.env` แล้วระบบยังไม่เปลี่ยน:

```powershell
php artisan optimize:clear
```

ถ้า storage link มีอยู่แล้ว:

```text
The public/storage link already exists.
```

ไม่ต้องตกใจ ใช้งานต่อได้


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
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan optimize:clear
php artisan serve
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
php artisan key:generate
php artisan storage:link
php artisan migrate:fresh --seed
php artisan optimize:clear
php artisan serve
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
php artisan key:generate --show
```

นำค่าที่ได้ไปใส่ใน Railway Variable: `APP_KEY`
