# Production Checklist v29 Stable Release

## Railway Variables ที่ต้องมี
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://raikhaisaengstrawberrydemo-production.up.railway.app`
- `ALLOW_DEMO_SEED=false`

## Social Login Variables
ใส่เมื่อพร้อมใช้งานจริง
- `GOOGLE_CLIENT_ID`
- `GOOGLE_CLIENT_SECRET`
- `GOOGLE_REDIRECT_URI=https://raikhaisaengstrawberrydemo-production.up.railway.app/auth/google/callback`
- `FACEBOOK_CLIENT_ID`
- `FACEBOOK_CLIENT_SECRET`
- `FACEBOOK_REDIRECT_URI=https://raikhaisaengstrawberrydemo-production.up.railway.app/auth/facebook/callback`
- `LINE_CLIENT_ID`
- `LINE_CLIENT_SECRET`
- `LINE_REDIRECT_URI=https://raikhaisaengstrawberrydemo-production.up.railway.app/auth/line/callback`

## ก่อน Deploy
1. เข้า Admin > Backup / Export เพื่อดาวน์โหลด CSV สำหรับอ้างอิงการดำเนินงานได้ แต่ CSV นี้ไม่ใช่ database backup และไม่มี payment metadata หรือไฟล์สลิป
2. ตรวจ `APP_DEBUG=false`
3. ตรวจ `ALLOW_DEMO_SEED=false` เพื่อกันข้อมูล Demo ทับข้อมูลจริง
4. ตรวจ `APP_ENV=production` แล้วเปิดหน้า Login เพื่อยืนยันว่าไม่แสดงบัญชีหรือรหัสผ่าน Demo แต่ปุ่ม Social Login ยังอยู่ครบ
5. ตรวจว่า `.env` ไม่อยู่ใน Git, ZIP, artifact, log หรือไฟล์ที่ส่งให้บุคคลอื่น ห้าม commit หรือเผยแพร่ `.env` โดยเด็ดขาด
6. Commit และ Push GitHub ให้เรียบร้อย

## ก่อนย้าย Payment Slip เดิม

1. กำหนด maintenance window หรือหยุดการเขียนข้อมูลชั่วคราว แล้วสร้าง database dump หรือ snapshot ของฐานข้อมูลจริงที่สามารถ restore ได้ เก็บสำเนานอกเครื่อง Deploy
2. คัดลอกหรือ snapshot โฟลเดอร์ต่อไปนี้ไปยัง disk/bucket ภายนอก โดยรักษา path เดิม (บันทึกไว้หากโฟลเดอร์ใดยังไม่มี):

```text
storage/app/public/slips/
storage/app/public/payment_slips/
storage/app/private/payment_slips/
```

3. ทดสอบ restore database ไปยัง instance ที่แยกจาก Production และยืนยันว่าไม่มี error เทียบจำนวนแถวใน `payments` แล้วสุ่มตรวจ `slip_disk`/`slip_path` กับไฟล์ที่คัดลอก รวมถึงขนาดหรือ checksum ของไฟล์ตัวอย่าง
4. Admin > Backup / Export ใช้เป็น CSV ประกอบการตรวจสอบได้เท่านั้น **ห้ามใช้แทน** database dump/snapshot และสำเนาไฟล์สลิป
5. รัน dry-run ก่อนเสมอและตรวจว่า failed เป็น 0:

```bash
php artisan payments:migrate-slips-private --dry-run
```

6. เมื่อตรวจรายการและยืนยันว่า backup restore ได้แล้วจึงรันจริง:

```bash
php artisan payments:migrate-slips-private
```

7. หลังรัน ตรวจว่า failed เป็น 0, metadata ชี้ไป `local:payment_slips/...`, ผู้ดูแลที่มีสิทธิ์เปิดสลิปตัวอย่างได้ และสำเนาภายนอกยังอยู่ครบ อย่าลบ backup ก่อนพ้นระยะเวลาเก็บรักษา

ห้ามลบไฟล์ public ด้วยตนเอง หากคำสั่งรายงาน failed ให้หยุด ตรวจ log/backup และแก้สาเหตุก่อนรันใหม่

## ตรวจบัญชี Demo ที่อาจมีอยู่จาก Deployment เดิม

การตั้ง `ALLOW_DEMO_SEED=false` ป้องกันการสร้างบัญชีใหม่ แต่ไม่ลบบัญชีที่ถูกสร้างไปแล้ว ให้ค้นหาบัญชีที่เคยใช้รหัสผ่านสาธารณะด้วย query แบบอ่านอย่างเดียวก่อน:

```sql
SELECT id, email, role, is_active
FROM users
WHERE email IN (
  'admin@maeyangha.test',
  'member@maeyangha.test',
  'user_test@khaisaeng.test',
  'admin_test@khaisaeng.test',
  'sbadmin_test@khaisaeng.test'
);
```

- ถ้าไม่ใช้งานจริง ให้ปิดใช้งานทันที ตรวจข้อมูลที่เชื่อมโยง/เก็บหลักฐานตามนโยบาย แล้วจึงลบผ่านขั้นตอนผู้ดูแลที่รองรับ
- ถ้าจำเป็นต้องเก็บบัญชี ให้เปลี่ยนอีเมลตามเหมาะสม เปลี่ยนเป็นรหัสผ่านสุ่มที่ไม่ซ้ำและไม่เปิดเผย ตรวจ role ให้เหลือสิทธิ์ขั้นต่ำ และยกเลิก session/token เดิม
- บันทึกผู้ดำเนินการ เวลา และผลตรวจสอบ ห้ามใส่รหัสผ่านใหม่ลง Git, `.env`, log หรือเอกสารนี้

## หลัง Deploy
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

เมื่อ `APP_ENV=production` และ `ALLOW_DEMO_SEED=false` คำสั่ง `db:seed` จะไม่สร้างบัญชีที่ใช้รหัสผ่านสาธารณะและจะไม่รันชุดข้อมูล `DemoProductionSeeder` แต่ยังลงข้อมูลอ้างอิงที่ระบบต้องใช้ หลังเปลี่ยน Variables ต้องรัน `php artisan optimize:clear` **ก่อน** `db:seed` และก่อนตรวจหน้า Login เพื่อไม่ให้ config cache เก่าทำให้ environment หรือ flag ไม่ตรงกับค่าปัจจุบัน

ตั้ง process สำหรับ scheduler ให้ทำงานต่อเนื่องด้วย `php artisan schedule:work` หรือ cron ที่เรียก `php artisan schedule:run` ทุกนาที เพื่อให้งานหมดอายุคำสั่งซื้อทำงานจริง

## ทดสอบหลัง Deploy
- หน้าแรก / หน้าสินค้า / รายละเอียดสินค้า เปิดได้
- Login Member/Admin/Super Admin ได้
- Admin > สินค้า: แก้สินค้าแล้วกลับหน้าเดิม/เลื่อนตำแหน่งล่าสุดได้
- Admin > โฆษณา / โปรโมชั่น: แก้รูปข้างโปรโมชั่น ข้อความ ปุ่ม ลิงก์ได้
- Admin > Activity Log: หลังแก้ข้อมูลสำคัญต้องมีประวัติขึ้น
- Admin > Backup / Export: ดาวน์โหลด CSV ได้
- Admin > Social Login Status: Callback URL แสดงถูกต้อง
- Checkout: VAT + ค่าส่ง คำนวณถูกต้อง
- จังหวัด/อำเภอ/ตำบล/ไปรษณีย์ โหลดได้
- ยกเลิกออเดอร์แล้วคืน Stock แค่ครั้งเดียว
- Login ผิดเกิน 5 ครั้ง ถูกหน่วงชั่วคราว
- หน้า Login Production ไม่แสดงอีเมล Demo ทั้งสามบัญชีและไม่แสดงรหัสผ่าน Demo
- Social Login ยังแสดงและทำงานตามค่าที่ตั้งไว้
- ไม่ขึ้น Chrome “Send anyway” หลังบันทึกฟอร์ม


## ตรวจหลัง v29.1
- [ ] หน้ารายละเอียดสินค้า ปุ่ม − / + กดง่ายและเหมือนหน้าตะกร้า
- [ ] Footer map ปักตำแหน่ง 18.854859, 98.561256
- [ ] เบอร์คุณตี๋เป็น 089-265-5685
