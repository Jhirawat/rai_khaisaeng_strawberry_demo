# ตั้งค่าเข้าสู่ระบบด้วย Google, Facebook และ LINE

ระบบและหน้าปุ่มเข้าสู่ระบบถูกเตรียมไว้แล้ว แต่แต่ละผู้ให้บริการต้องใช้ Client ID และ Client Secret ของเจ้าของเว็บไซต์ จึงไม่ควรเขียนค่าจริงลง GitHub

## Callback URL

สำหรับเครื่องพัฒนาในปัจจุบัน:

```text
http://127.0.0.1:8000/auth/google/callback
http://127.0.0.1:8000/auth/facebook/callback
http://127.0.0.1:8000/auth/line/callback
```

สำหรับเว็บไซต์ Railway ปัจจุบัน:

```text
https://raikhaisaengstrawberrydemo-production.up.railway.app/auth/google/callback
https://raikhaisaengstrawberrydemo-production.up.railway.app/auth/facebook/callback
https://raikhaisaengstrawberrydemo-production.up.railway.app/auth/line/callback
```

นำ URL ของผู้ให้บริการแต่ละรายไปใส่ในหน้า Developer Console ของ Google, Meta for Developers และ LINE Developers ให้ตรงกันทุกตัวอักษร

## ตัวแปรที่ต้องตั้ง

ตั้งค่าในไฟล์ `.env` สำหรับเครื่องพัฒนา หรือ Variables ของ Railway สำหรับระบบจริง:

```dotenv
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

หลังบันทึกค่า ให้รัน:

```powershell
php artisan optimize:clear
```

จากนั้นเปิด `/login` ปุ่มที่ตั้งค่าครบจะเปลี่ยนจากสถานะ `Setup` เป็นปุ่มที่กดเข้าสู่ระบบได้

## ข้อควรระวัง

- ห้าม commit หรือส่ง Client Secret ขึ้น GitHub
- Production ควรใช้ HTTPS เท่านั้น
- ตรวจให้โดเมนและ Callback URL ใน Developer Console ตรงกับ `APP_URL`
- Google/Meta/LINE อาจกำหนดให้กรอกข้อมูลแอป นโยบายความเป็นส่วนตัว หรือส่งแอปตรวจสอบก่อนเปิดให้ผู้ใช้ทั่วไป
