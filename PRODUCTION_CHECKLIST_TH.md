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
4. Commit และ Push GitHub ให้เรียบร้อย

## หลัง Deploy
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

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
- ไม่ขึ้น Chrome “Send anyway” หลังบันทึกฟอร์ม


## ตรวจหลัง v29.1
- [ ] หน้ารายละเอียดสินค้า ปุ่ม − / + กดง่ายและเหมือนหน้าตะกร้า
- [ ] Footer map ปักตำแหน่ง 18.854859, 98.561256
- [ ] เบอร์คุณตี๋เป็น 089-265-5685
