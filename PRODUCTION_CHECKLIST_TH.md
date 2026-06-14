ระบบหลักครบประมาณ 90%: Auth, RBAC, CRUD, Cart, Checkout, Payment Slip, Stock Deduct, Dashboard Real Data, Reports Summary. ส่วนที่ควรเพิ่มเพื่อ 95-100%: Unit Tests, Email Verification จริง, PDF/Excel export file จริง, payment gateway จริง, deployment hardening.


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
