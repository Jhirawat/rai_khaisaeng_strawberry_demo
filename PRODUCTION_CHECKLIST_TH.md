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
1. เข้า Admin > Backup / Export แล้ว Export ข้อมูลสำคัญ
2. ตรวจ `APP_DEBUG=false`
3. ตรวจ `ALLOW_DEMO_SEED=false` เพื่อกันข้อมูล Demo ทับข้อมูลจริง
4. ตรวจ `APP_ENV=production` แล้วเปิดหน้า Login เพื่อยืนยันว่าไม่แสดงบัญชีหรือรหัสผ่าน Demo แต่ปุ่ม Social Login ยังอยู่ครบ
5. ตรวจว่า `.env` ไม่อยู่ใน Git, ZIP, artifact, log หรือไฟล์ที่ส่งให้บุคคลอื่น ห้าม commit หรือเผยแพร่ `.env` โดยเด็ดขาด
6. Commit และ Push GitHub ให้เรียบร้อย

## ก่อนย้าย Payment Slip เดิม

1. เข้า Admin > Backup / Export และดาวน์โหลดข้อมูลสำรองเก็บไว้นอกเครื่อง Deploy
2. รัน dry-run ก่อนเสมอและตรวจว่า failed เป็น 0:

```bash
php artisan payments:migrate-slips-private --dry-run
```

3. เมื่อตรวจรายการและ backup แล้วจึงรันจริง:

```bash
php artisan payments:migrate-slips-private
```

ห้ามลบไฟล์ public ด้วยตนเอง หากคำสั่งรายงาน failed ให้หยุด ตรวจ log/backup และแก้สาเหตุก่อนรันใหม่

## หลัง Deploy
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

หลังเปลี่ยน Variables ต้องรัน `php artisan optimize:clear` ก่อนตรวจหน้า Login เพื่อไม่ให้ config cache เก่าทำให้ environment ไม่ตรงกับ `APP_ENV=production`

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
