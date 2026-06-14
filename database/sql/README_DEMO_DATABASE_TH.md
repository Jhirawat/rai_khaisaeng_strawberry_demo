# Demo Production Database v22-23

ไฟล์ชุดนี้เพิ่มข้อมูลทดสอบระบบแบบสมจริงผ่าน Laravel Seeder

## คำสั่งสร้างฐานข้อมูลทดสอบ

```bash
php artisan migrate:fresh --seed
```

Seeder หลักที่เพิ่มในเวอร์ชันนี้:

- `database/seeders/DemoProductionSeeder.php`

ข้อมูลที่สร้าง:

- ผู้ใช้งานทดสอบ 60+ คน
- Admin / Staff / Super Admin / Member
- หมวดหมู่สินค้า 8 หมวด
- สินค้า 72 รายการ พร้อมรูป SVG
- สต็อกปกติ / ใกล้หมด / หมดสต็อก
- คำสั่งซื้อย้อนหลังประมาณ 620 รายการ
- ยอดขายรายวัน / เดือน / ปี ให้ Dashboard ดึงจากฐานข้อมูลจริง
- Payment + Slip demo สำหรับ QR/OCR/Manual Review
- Shipment / Review / Notifications

## หมายเหตุ

ไฟล์รูปสินค้าทดสอบอยู่ที่:

```text
public/images/products/demo-product-*.svg
```

ไฟล์สลิปทดสอบอยู่ที่:

```text
storage/app/public/slips/demo-slip-*.svg
```

หลังรัน Seeder ให้รัน:

```bash
php artisan storage:link
```
