# CHANGELOG - Rai Khaisaeng Strawberry Prototype v29

## v29 - Production Stable Release
- ยกระดับจาก v28 เป็น Production Stable สำหรับใช้งานจริง/ส่งงาน
- เพิ่ม Activity Log ให้ครอบคลุมมากขึ้น:
  - Login / Logout / Register / Social Login
  - เพิ่ม/แก้ไข/ลบหมวดหมู่
  - ปรับสต๊อกเดี่ยวและปรับสต๊อกหลายรายการ
  - ตั้งค่าข้อมูลร้านค้า / โลโก้ / Hero Image
  - ตั้งค่าธีม / Preset Theme / Reset Theme
- คง Activity Log เดิมจาก v28: สินค้า, โปรโมชั่น, ออเดอร์, การชำระเงิน
- คง Backup / Export CSV: สินค้า, ออเดอร์, สมาชิก, คลังสินค้า
- คง Social Login Status พร้อม Callback URL สำหรับ Google / Facebook / LINE
- คง Security Headers + Force HTTPS + Login Rate Limit
- คง Seed Protection: Production ไม่รัน DemoProductSeeder ถ้า `ALLOW_DEMO_SEED=false`
- คง Edge Case สำคัญ: ยกเลิกออเดอร์แล้วคืน Stock แค่ครั้งเดียว
- คง Promotion Editor: แก้รูปข้างโปรโมชั่น, หัวข้อ, รายละเอียด, ปุ่ม, ลิงก์
- คง UI Fixes: ปุ่ม/Badge/รายละเอียดไม่ติดขอบบน และ Mobile Admin ดีขึ้น

## คำสั่งหลัง Deploy
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

> หมายเหตุ: ถ้า `storage:link` ขึ้นว่า link already exists ถือว่าปกติ


## v29.1 Quick Production Fix
- ปรับปุ่มเพิ่ม/ลดจำนวนในหน้ารายละเอียดสินค้าให้ใช้รูปแบบเดียวกับหน้าตะกร้า (+ / −) และกดง่ายขึ้น
- ปรับแผนที่ Footer ให้ปักตำแหน่งพิกัด 18.854859, 98.561256 และลิงก์ Google Maps ใหม่
- แก้เบอร์คุณตี๋เป็น 089-265-5685 และเพิ่ม migration อัปเดตค่าบน Production
