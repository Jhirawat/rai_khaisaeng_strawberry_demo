@extends('layouts.admin')
@section('title',$promotion->exists?'แก้ไขโปรโมชั่น':'เพิ่มโปรโมชั่น')
@section('content')
<form method="post" enctype="multipart/form-data" action="{{$promotion->exists?route('admin.promotions.update',$promotion):route('admin.promotions.store')}}" class="content-card p-4">@csrf @if($promotion->exists) @method('PUT') @endif
@if($errors->any())<div class="alert alert-danger rounded-4"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul></div>@endif
<div class="alert alert-info rounded-4 mb-4">
    <strong>ต้องการแก้รูปข้างโปรโมชั่นหน้าแรก?</strong> ให้เลือกตำแหน่ง <strong>หน้าแรก: รูปข้างโปรโมชั่น</strong> แล้วอัปโหลดรูปในช่อง “รูปข้างโปรโมชั่นด้านซ้าย”
</div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="row g-3">
            <div class="col-md-8"><label class="form-label">หัวข้อโปรโมชั่น</label><input name="title" class="form-control rounded-pill" value="{{old('title',$promotion->title)}}" required></div>
            <div class="col-md-4"><label class="form-label">ตำแหน่งที่แสดง</label><select name="position" class="form-select rounded-pill"><option value="home_banner" @selected(old('position',$promotion->position)==='home_banner')>หน้าแรก: รูปข้างโปรโมชั่น</option><option value="checkout_notice" @selected(old('position',$promotion->position)==='checkout_notice')>แจ้งเตือนหน้า Checkout</option><option value="product_banner" @selected(old('position',$promotion->position)==='product_banner')>แบนเนอร์หน้าสินค้า</option></select></div>
            <div class="col-md-6"><label class="form-label">คำโปรย / Label ด้านบน</label><input name="subtitle" class="form-control rounded-pill" value="{{old('subtitle',$promotion->subtitle)}}" placeholder="Promotion / Advertising Area"></div>
            <div class="col-md-3"><label class="form-label">ข้อความปุ่ม</label><input name="button_text" class="form-control rounded-pill" value="{{old('button_text',$promotion->button_text)}}" placeholder="ดูสินค้าโปรโมชั่น"></div>
            <div class="col-md-3"><label class="form-label">ลิงก์ปุ่ม</label><input name="button_url" class="form-control rounded-pill" value="{{old('button_url',$promotion->button_url)}}" placeholder="/products"></div>
            <div class="col-12"><label class="form-label">รายละเอียดโปรโมชั่น</label><textarea name="description" rows="5" class="form-control rounded-4">{{old('description',$promotion->description)}}</textarea></div>
            <div class="col-md-2"><label class="form-label">ลำดับ</label><input type="number" name="sort_order" class="form-control rounded-pill" value="{{old('sort_order',$promotion->sort_order ?? 0)}}"></div>
            <div class="col-md-5"><label class="form-label">เริ่มแสดง</label><input type="datetime-local" name="starts_at" class="form-control rounded-pill" value="{{old('starts_at',optional($promotion->starts_at)->format('Y-m-d\TH:i'))}}"></div>
            <div class="col-md-5"><label class="form-label">สิ้นสุด</label><input type="datetime-local" name="ends_at" class="form-control rounded-pill" value="{{old('ends_at',optional($promotion->ends_at)->format('Y-m-d\TH:i'))}}"></div>
            <div class="col-12"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active',$promotion->is_active ?? true))> เปิดใช้งาน</label></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="border rounded-4 p-3 bg-light h-100">
            <label class="form-label fw-bold"><i class="bi bi-image me-1"></i> รูปข้างโปรโมชั่นด้านซ้าย</label>
            <input type="file" name="image" class="form-control rounded-pill" accept="image/*">
            <div class="small text-muted mt-2">แนะนำขนาด 900×520 px หรือสัดส่วน 16:9 / 3:2 ไฟล์ jpg, png, webp ไม่เกิน 4MB</div>
            @if($promotion->image_url)
                <img src="{{$promotion->image_url}}" class="img-fluid rounded-4 mt-3 shadow-sm" style="width:100%;height:220px;object-fit:cover" alt="รูปโปรโมชั่นเดิม">
                <label class="form-check mt-2"><input class="form-check-input" type="checkbox" name="remove_image" value="1"> ลบรูปเดิม</label>
            @else
                <div class="rounded-4 border bg-white d-flex align-items-center justify-content-center mt-3" style="height:220px"><span class="text-muted">ยังไม่มีรูป</span></div>
            @endif
        </div>
    </div>
    <div class="col-12 d-flex justify-content-between">
        <a class="btn btn-outline-secondary rounded-pill px-4" href="{{route('admin.promotions.index')}}">กลับ</a>
        <button class="btn btn-admin rounded-pill px-5"><i class="bi bi-save me-1"></i> บันทึกโปรโมชั่น</button>
    </div>
</div>
</form>
@endsection
