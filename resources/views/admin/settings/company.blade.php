@extends('layouts.admin')
@section('title','ตั้งค่าข้อมูลร้านค้า')
@section('content')
<div class="content-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <div class="text-muted small">หน้าหลัก &gt; ตั้งค่าระบบ &gt; ข้อมูลร้านค้า</div>
            <h2 class="fw-bold mb-1">ตั้งค่าข้อมูลร้านค้า / ใบเสร็จ</h2>
            <p class="text-muted mb-0">ข้อมูลนี้จะถูกนำไปใช้ในใบเสร็จ Footer แผนที่ และข้อมูลติดต่อหน้าร้าน</p>
        </div>
        <span class="badge rounded-pill text-bg-success px-3 py-2">Company Profile</span>
    </div>
</div>
<form method="POST" action="{{route('admin.company-settings.update')}}" enctype="multipart/form-data">
@csrf @method('PATCH')
<div class="row g-4">
    <div class="col-lg-7">
        <div class="content-card p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-shop me-2 text-danger"></i>ข้อมูลร้านค้า</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">ชื่อร้าน / วิสาหกิจ</label><input class="form-control rounded-pill" name="company_name" value="{{old('company_name',$settings['company_name'])}}"></div>
                <div class="col-md-6"><label class="form-label">ชื่อร้านภาษาอังกฤษ</label><input class="form-control rounded-pill" name="company_name_en" value="{{old('company_name_en',$settings['company_name_en'])}}"></div>
                <div class="col-12"><label class="form-label">คำอธิบายกิจการ</label><input class="form-control rounded-pill" name="company_subtitle" value="{{old('company_subtitle',$settings['company_subtitle'])}}"></div>
                <div class="col-12"><label class="form-label">ที่ตั้งภาษาไทย</label><textarea class="form-control rounded-4" name="company_address" rows="3">{{old('company_address',$settings['company_address'])}}</textarea></div>
                <div class="col-12"><label class="form-label">ที่ตั้งภาษาอังกฤษ</label><textarea class="form-control rounded-4" name="company_address_en" rows="3">{{old('company_address_en',$settings['company_address_en'])}}</textarea></div>
                <div class="col-md-6"><label class="form-label">เลขประจำตัวผู้เสียภาษี</label><input class="form-control rounded-pill" name="company_tax_id" value="{{old('company_tax_id',$settings['company_tax_id'])}}"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input class="form-control rounded-pill" name="company_email" value="{{old('company_email',$settings['company_email'])}}"></div>
                <div class="col-12"><label class="form-label">ลิงก์ Google Maps</label><input class="form-control rounded-pill" name="company_map_url" value="{{old('company_map_url',$settings['company_map_url'])}}"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">

        <div class="content-card p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-image me-2 text-danger"></i>โลโก้ / Favicon</h5>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">โลโก้ Navbar ภาษาไทย</label>
                    <input type="file" class="form-control rounded-pill" name="site_logo_th_file" accept="image/*,.ico">
                    <div class="mt-2 border rounded-4 p-2 bg-light"><img src="{{asset($settings['site_logo_th'])}}" style="max-height:70px;max-width:100%" alt="logo th"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">โลโก้ Navbar ภาษาอังกฤษ</label>
                    <input type="file" class="form-control rounded-pill" name="site_logo_en_file" accept="image/*,.ico">
                    <div class="mt-2 border rounded-4 p-2 bg-light"><img src="{{asset($settings['site_logo_en'])}}" style="max-height:70px;max-width:100%" alt="logo en"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">โลโก้ใบเสร็จภาษาไทย</label>
                    <input type="file" class="form-control rounded-pill" name="receipt_logo_th_file" accept="image/*,.ico">
                    <div class="mt-2 border rounded-4 p-2 bg-light"><img src="{{asset($settings['receipt_logo_th'])}}" style="max-height:90px;max-width:100%" alt="receipt logo th"></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">โลโก้ใบเสร็จภาษาอังกฤษ</label>
                    <input type="file" class="form-control rounded-pill" name="receipt_logo_en_file" accept="image/*,.ico">
                    <div class="mt-2 border rounded-4 p-2 bg-light"><img src="{{asset($settings['receipt_logo_en'])}}" style="max-height:90px;max-width:100%" alt="receipt logo en"></div>
                </div>
                <div class="col-12">
                    <label class="form-label">Favicon</label>
                    <input type="file" class="form-control rounded-pill" name="site_favicon_file" accept="image/*,.ico">
                    <div class="mt-2 d-flex align-items-center gap-2"><img src="{{asset($settings['site_favicon'])}}" style="width:38px;height:38px;object-fit:contain" alt="favicon"><span class="text-muted small">ใช้แสดงบนแท็บ Browser</span></div>
                </div>
            </div>
        </div>
        <div class="content-card p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-telephone me-2 text-danger"></i>เบอร์ติดต่อ</h5>
            @for($i=1;$i<=3;$i++)
            <div class="row g-2 mb-3">
                <div class="col-5"><input class="form-control rounded-pill" name="company_phone_{{$i}}_name" value="{{old('company_phone_'.$i.'_name',$settings['company_phone_'.$i.'_name'])}}" placeholder="ชื่อผู้ติดต่อ"></div>
                <div class="col-7"><input class="form-control rounded-pill" name="company_phone_{{$i}}" value="{{old('company_phone_'.$i,$settings['company_phone_'.$i])}}" placeholder="เบอร์โทร"></div>
            </div>
            @endfor
        </div>

        <div class="content-card p-4 mb-4">
            <h5 class="fw-bold mb-3"><i class="bi bi-share me-2 text-danger"></i>ช่องทางติดต่อ Social</h5>
            <div class="mb-3"><label class="form-label"><i class="bi bi-facebook text-primary me-1"></i>Facebook URL</label><input class="form-control rounded-pill" name="social_facebook_url" value="{{old('social_facebook_url',$settings['social_facebook_url'] ?? '#')}}" placeholder="https://facebook.com/..."></div>
            <div class="mb-3"><label class="form-label"><i class="bi bi-youtube text-danger me-1"></i>YouTube URL</label><input class="form-control rounded-pill" name="social_youtube_url" value="{{old('social_youtube_url',$settings['social_youtube_url'] ?? '#')}}" placeholder="https://youtube.com/..."></div>
            <div class="mb-0"><label class="form-label"><i class="bi bi-line text-success me-1"></i>LINE URL</label><input class="form-control rounded-pill" name="social_line_url" value="{{old('social_line_url',$settings['social_line_url'] ?? '#')}}" placeholder="https://line.me/..."></div>
        </div>
        <div class="content-card p-4">
            <h5 class="fw-bold mb-3">ตัวอย่างข้อมูลบนใบเสร็จ</h5>
            <div class="border rounded-4 p-3 bg-light">
                <h5 class="fw-bold mb-1">{{$settings['company_name']}}</h5>
                <div>{{$settings['company_subtitle']}}</div>
                <div class="small mt-2">{{$settings['company_address']}}</div>
                <div class="small">เลขผู้เสียภาษี: {{$settings['company_tax_id']}}</div>
                <div class="small">Email: {{$settings['company_email']}}</div>
                <div class="small">โทร: {{$settings['company_phone_1']}}, {{$settings['company_phone_2']}}, {{$settings['company_phone_3']}}</div>
            </div>
        </div>
    </div>
</div>
<div class="position-sticky bottom-0 bg-white p-3 rounded-4 shadow-sm mt-4 d-flex justify-content-end gap-2">
    <a href="{{route('admin.dashboard')}}" class="btn btn-light rounded-pill px-4">ยกเลิก</a>
    <button class="btn btn-danger rounded-pill px-5">บันทึกข้อมูลร้านค้า</button>
</div>
</form>
@endsection
