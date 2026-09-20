# CHANGELOG - Rai Khaisaeng Strawberry Prototype v30

## v30.1 - Urgent Commerce Hardening
- รวมกติกาสถานะออเดอร์ไว้ที่ `OrderWorkflowService` และปฏิเสธการข้าม/ย้อนสถานะที่ไม่ถูกต้อง โดยมีสถานะ `confirmed` แยกออเดอร์เก็บเงินปลายทางออกจากออเดอร์โอนที่ยังไม่ชำระ
- เพิ่มคำสั่งและ Scheduler สำหรับหมดอายุออเดอร์โอน/QR ที่ค้างชำระ พร้อมล็อกข้อมูล คืนสต๊อก และบันทึกประวัติเพียงครั้งเดียวแม้เรียกซ้ำหรือประมวลผลบางรายการไม่สำเร็จ
- ทำให้การอนุมัติ/ปฏิเสธการชำระเงินและการเปลี่ยนสถานะออเดอร์เป็น Transaction เดียวกัน ส่วนยอด COD จะยืนยันหรือปฏิเสธจากผลการจัดส่งเท่านั้น
- เก็บสลิปใหม่ไว้ใน Private Storage และเปิดดูผ่าน Route ที่ตรวจสิทธิ์พนักงานเท่านั้น พร้อมคำสั่ง `payments:migrate-slips-private` สำหรับย้ายสลิปเดิมแบบตรวจ checksum, dry-run และรันซ้ำได้โดยไม่แตะไฟล์ตัวอย่าง
- ป้องกันการลบสมาชิกที่มีประวัติออเดอร์และหมวดหมู่ที่ยังมีสินค้า (รวมสินค้าที่ soft delete) พร้อมใช้ row lock ร่วมกับ Checkout เพื่อลดความเสี่ยงข้อมูลธุรกรรมสูญหาย
- ซ่อนบัญชีตัวอย่างนอก Local Environment และกำหนดให้การสร้างบัญชีรหัสผ่านที่ทราบล่วงหน้าต้องเปิด `ALLOW_DEMO_SEED` อย่างชัดเจน พร้อมปรับคู่มือ Local/Production, Backup และ Scheduler
- เพิ่มชุดทดสอบ PHPUnit แบบบังคับ SQLite `:memory:` ครอบคลุม lifecycle, expiry, stock idempotence, payment/COD, สิทธิ์สลิป, deletion guards, demo visibility และ database isolation; รอบตรวจสอบนี้ผ่าน 123 tests / 704 assertions
- การตรวจ MySQL/HTTP จริงและ lock contention ถูกเลื่อนออกไป เนื่องจาก XAMPP MySQL ที่ `127.0.0.1:3306` ไม่เปิดรับการเชื่อมต่อในเครื่องตรวจสอบ และไม่มีการใช้ฐานข้อมูล Development แทน
- Pint ทั้ง Repository และไฟล์ Seeder ที่สร้างจากชุดที่อยู่ขนาดใหญ่ยังใช้เวลานานเกินกรอบตรวจสอบ; Scoped Pint ยืนยันว่า `routes` และ `tests` ผ่าน แต่ยังพบ style/line-ending debt เดิมใน `app`, `bootstrap`, `config`, migrations และ seeders ซึ่งควรแยกทำเป็นงาน formatting เพื่อลดความเสี่ยงจาก diff ขนาดใหญ่

## v30 - Storefront & Member Login Refresh
- ปรับหน้าแรกให้เป็นหน้าร้านสตรอว์เบอร์รีแบบพรีเมียม รองรับมือถือและเดสก์ท็อป
- ปรับหน้าล็อกอินและสมัครสมาชิก พร้อมปุ่ม Google, Facebook และ LINE
- เพิ่ม Design System กลางให้หน้าสินค้า สมาชิก และหลังบ้านเป็นภาพเดียวกัน พร้อม active navigation และ mobile polish
- ปรับหน้ารวมสินค้า/รายละเอียดสินค้า: สถานะสต๊อก, empty search, ป้องกันซื้อสินค้าหมด และปิด URL ของสินค้าที่หยุดขาย
- ปรับ Checkout ให้คำนวณ VAT ค่าส่ง และยอดชำระใหม่ทันทีเมื่อเปลี่ยนจังหวัด ป้องกันยอดบนหน้าจอไม่ตรงกับออเดอร์
- ปรับตะกร้า AJAX ให้แจ้ง error จริงเมื่อสต๊อกไม่พอ และเพิ่มหน้า confirmation/timeline/สรุปยอด/ที่อยู่ในรายละเอียดออเดอร์
- เพิ่ม pagination ประวัติคำสั่งซื้อ และแก้ responsive table ที่กระทบหน้าสมาชิกบนมือถือ
- เพิ่มความปลอดภัยหลังบ้าน: ตัด session บัญชีที่ถูกระงับ, ป้องกันแก้สิทธิ์ตนเอง, ใช้รหัสชั่วคราวแบบสุ่ม และห้ามเปิดออเดอร์ที่คืนสต๊อกแล้ว
- แก้ KPI สินค้าขายดีและยอดขายตามหมวดไม่ให้นับออเดอร์ยกเลิก
- ยกระดับ Inventory ให้กรอกจำนวนโดยตรงและบันทึกหลายรายการใน Transaction เดียว พร้อม Activity Log เมื่อแก้เกณฑ์แจ้งเตือน
- ปรับ Orders หลังบ้านให้บันทึกเฉพาะรายการที่เปลี่ยน ลดความเสี่ยงจากการแก้สถานะโดยไม่ตั้งใจ
- ถอดช่อง Product Variants/SEO ที่ยังไม่บันทึกจริงออก ป้องกันความเข้าใจผิดในหน้าแก้สินค้า
- ปรับ Company Settings ให้ตรวจลิงก์, ลบไฟล์ตั้งค่าเก่าเมื่ออัปโหลดใหม่ และแสดงตัวอย่างข้อมูล/รูปแบบสดก่อนบันทึก
- ปรับใบเสร็จให้ใช้ชื่อผู้มีอำนาจลงนามจาก Settings, รองรับมือถือ และติดป้ายเอกสารตัวอย่างเมื่อข้อมูลภาษียังไม่พร้อม
- ปรับ Users, Dashboard และรายละเอียดออเดอร์ให้ responsive และสื่อสถานะตามข้อมูลจริง
- แสดงสถานะปุ่ม Social Login ตามค่าที่ตั้งจริง ป้องกันผู้ใช้กดเข้าระบบที่ยังไม่ได้ใส่ Client ID/Secret
- เพิ่มความปลอดภัยของ Social Login ด้วย OAuth state, ตรวจสมาชิกที่ถูกระงับ และไม่อนุญาตบัญชีแอดมิน/พนักงานล็อกอินผ่าน Social Login
- เปลี่ยนเส้นทางหลังล็อกอินกลับไปยังหน้าที่สมาชิกตั้งใจเข้าก่อนหน้า เช่น ตะกร้าหรือชำระเงิน
- เพิ่ม Composer lock file เพื่อให้ทุกเครื่องติดตั้ง dependency รุ่นเดียวกัน
- เพิ่มคู่มือตั้งค่า Social Login ที่ `SOCIAL_LOGIN_SETUP_TH.md`

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
