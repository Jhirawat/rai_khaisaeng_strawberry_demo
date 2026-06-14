@extends('layouts.admin')
@section('title',isset($product)?'แก้ไขสินค้า':'เพิ่มสินค้า')
@section('content')
<form method="post" enctype="multipart/form-data" action="{{isset($product)?route('admin.products.update',$product):route('admin.products.store')}}" class="product-editor-form" id="productEditorForm">
@csrf @isset($product) @method('put') @endisset
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div><h1 class="fw-bold mb-1">{{isset($product)?'แก้ไขสินค้า':'เพิ่มสินค้าใหม่'}}</h1><div class="text-muted">จัดวางแบบ 2 คอลัมน์ เพื่อแก้ไขข้อมูลสินค้าได้เร็วขึ้น</div></div>
    <a href="{{route('admin.products.index')}}" class="btn btn-light rounded-pill px-4">กลับรายการสินค้า</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="content-card p-4 mb-4">
            <h5 class="fw-bold mb-3">ข้อมูลทั่วไป</h5>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">ชื่อสินค้า (ไทย)</label><input class="form-control rounded-3" name="name" value="{{old('name',$product->name??'')}}" required placeholder="ชื่อสินค้า"></div>
                <div class="col-md-6"><label class="form-label">Product Name (English)</label><input class="form-control rounded-3" name="name_en" value="{{old('name_en',$product->name_en??'')}}" placeholder="English product name"></div>
                <div class="col-md-4"><label class="form-label">SKU</label><input class="form-control rounded-3" name="sku" value="{{old('sku',$product->sku??'')}}" placeholder="เว้นว่างเพื่อให้ระบบสร้างอัตโนมัติ"><small class="text-muted">ถ้าไม่กรอก ระบบจะสร้าง SKU อัตโนมัติและไม่ซ้ำ เช่น ST-FRESH-001</small></div>
                <div class="col-md-8"></div>
                <div class="col-12"><label class="form-label">รายละเอียดสินค้า (ไทย)</label><textarea class="form-control rounded-3" rows="6" name="description" placeholder="รายละเอียดสินค้า / วิธีใช้ / จุดเด่นสินค้า">{{old('description',$product->description??'')}}</textarea></div>
                <div class="col-12"><label class="form-label">Product Description (English)</label><textarea class="form-control rounded-3" rows="5" name="description_en" placeholder="English product description">{{old('description_en',$product->description_en??'')}}</textarea><small class="text-muted">ใช้สำหรับหน้า Guest / Member เมื่อเปลี่ยนภาษาเป็น EN</small></div>
            </div>
        </div>
        <div class="content-card p-4 mb-4">
            <h5 class="fw-bold mb-3">ราคาและคลังสินค้า</h5>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">ราคาขาย</label><input class="form-control rounded-3" name="price" value="{{old('price',$product->price??'')}}" required inputmode="decimal"></div>
                <div class="col-md-3"><label class="form-label">ต้นทุน</label><input class="form-control rounded-3" name="cost" value="{{old('cost',$product->cost??0)}}" inputmode="decimal"></div>
                <div class="col-md-3"><label class="form-label">จำนวนสต๊อก</label><input class="form-control rounded-3" name="quantity" value="{{old('quantity',$product->inventory->quantity??0)}}" required inputmode="numeric"></div>
                <div class="col-md-3"><label class="form-label">ขั้นต่ำแจ้งเตือน</label><input class="form-control rounded-3" name="low_stock_threshold" value="{{old('low_stock_threshold',$product->inventory->low_stock_threshold??10)}}" inputmode="numeric"></div>
            </div>
        </div>
        <div class="content-card p-4 mb-4">
            <h5 class="fw-bold mb-3">ตัวเลือกสินค้า / การจัดส่ง</h5>
            <div class="alert alert-light border mb-3">รองรับการต่อยอด Variants เช่น ขนาด สี โมเดล แยกราคาและรูปภาพในอนาคต</div>
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">น้ำหนัก (กรัม)</label><input class="form-control rounded-3" placeholder="เช่น 500"></div>
                <div class="col-md-3"><label class="form-label">กว้าง (ซม.)</label><input class="form-control rounded-3"></div>
                <div class="col-md-3"><label class="form-label">ยาว (ซม.)</label><input class="form-control rounded-3"></div>
                <div class="col-md-3"><label class="form-label">สูง (ซม.)</label><input class="form-control rounded-3"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="content-card p-4 mb-4">
            <h5 class="fw-bold mb-3">รูปภาพสินค้า</h5>
            <label class="upload-box w-100 text-center" for="productImagesInput">
                <i class="bi bi-cloud-arrow-up display-5 text-danger"></i>
                <div class="fw-bold mt-2">ลากวางหรือเลือกไฟล์</div>
                <div class="text-muted small">รูปแรกจะใช้เป็นรูปหลัก / แสดงตัวอย่างทันที</div>
                <input id="productImagesInput" type="file" name="images[]" multiple accept="image/*" class="d-none">
            </label>
            <div id="newImagePreview" class="row g-2 mt-3"></div>
            @if(isset($product) && $product->images->count())
                <div class="existing-images mt-3">
                    <div class="small fw-bold text-muted mb-2">รูปภาพที่มีอยู่</div>
                    <div class="row g-2">
                    @foreach($product->images as $img)
                        <div class="col-6">
                            <div class="image-card border rounded-4 p-2 bg-white h-100">
                                <img src="{{$img->url}}" class="img-fluid rounded-3 product-existing-img" alt="{{$product->name}}" onerror="this.src='{{asset('images/products/placeholder.svg')}}'">
                                <div class="d-flex gap-1 mt-2 flex-wrap">
                                    @if(!$img->is_primary)
                                        <button type="submit" form="setPrimaryImage{{$img->id}}" class="btn btn-sm btn-outline-primary rounded-pill flex-fill">ตั้งรูปหลัก</button>
                                    @else
                                        <span class="badge bg-success rounded-pill flex-fill py-2">รูปหลัก</span>
                                    @endif
                                    <button type="submit" form="deleteProductImage{{$img->id}}" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('ลบรูปนี้?')">ลบ</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    </div>
                </div>
            @endif
            <label class="form-check mt-3">
                <input class="form-check-input" type="checkbox" name="replace_images" value="1">
                <span class="form-check-label">ลบรูปเดิมทั้งหมด แล้วใช้รูปที่อัปโหลดใหม่แทน</span>
            </label>
            <small class="text-muted d-block mt-2">ถ้ารูปไม่ขึ้น ให้รัน <code>php artisan storage:link</code> หรือ <code>php artisan storage:unlink</code> แล้วสร้างใหม่</small>
        </div>
        <div class="content-card p-4 mb-4">
            <h5 class="fw-bold mb-3">หมวดหมู่และสถานะ</h5>
            <div class="mb-3"><label class="form-label">หมวดหมู่</label><select name="category_id" class="form-select rounded-3">@foreach($categories as $c)<option value="{{$c->id}}" @selected(($product->category_id??'')==$c->id)>{{$c->name}}</option>@endforeach</select></div>
            <div class="mb-3"><label class="form-label">สถานะการมองเห็น</label><select name="status" class="form-select rounded-3"><option value="active" @selected(old('status',$product->status??'active')==='active')>Published / พร้อมขาย</option><option value="inactive" @selected(old('status',$product->status??'active')==='inactive')>Hidden / ปิดการขาย</option></select></div>
            <label class="form-check"><input class="form-check-input" type="checkbox" name="featured" @checked(old('featured',$product->featured??false))> <span class="form-check-label">สินค้าแนะนำ</span></label>
        </div>
        <div class="content-card p-4 mb-4">
            <h5 class="fw-bold mb-3">SEO Settings</h5>
            <input class="form-control rounded-3 mb-2" placeholder="Meta Title">
            <textarea class="form-control rounded-3" rows="3" placeholder="Meta Description"></textarea>
        </div>
    </div>
</div>
<div class="sticky-action-bar">
    <div class="d-flex gap-2 flex-wrap"><a href="{{route('admin.products.index')}}" class="btn btn-outline-secondary rounded-pill px-4">กลับรายการสินค้า</a></div>
    <div class="d-flex gap-2"><a href="{{route('admin.products.index')}}" class="btn btn-light rounded-pill px-4">ยกเลิก</a><button class="btn btn-danger rounded-pill px-5 fw-bold">บันทึกข้อมูล</button></div>
</div>
</form>
@if(isset($product))
    @foreach($product->images as $img)
        <form id="deleteProductImage{{$img->id}}" method="post" action="{{route('admin.productImages.destroy',$img)}}" class="d-none">@csrf @method('delete')</form>
        <form id="setPrimaryImage{{$img->id}}" method="post" action="{{route('admin.productImages.primary',$img)}}" class="d-none">@csrf @method('patch')</form>
    @endforeach
@endif
<style>.upload-box{border:2px dashed #f0a6b0;border-radius:22px;padding:32px 16px;background:#fff7f8;cursor:pointer}.upload-box:hover{background:#fff0f2}.product-existing-img,.preview-img{height:96px;width:100%;object-fit:cover}.image-card{box-shadow:0 6px 18px rgba(0,0,0,.04)}.sticky-action-bar{position:sticky;bottom:0;z-index:20;background:#fff;border:1px solid #eee;border-radius:24px 24px 0 0;box-shadow:0 -10px 30px rgba(0,0,0,.08);padding:14px 22px;display:flex;justify-content:space-between;align-items:center;gap:12px}.content-card{background:#fff;border-radius:24px;box-shadow:0 12px 30px rgba(15,23,42,.06)}</style>
<script>
document.getElementById('productImagesInput')?.addEventListener('change', function(){
    const preview = document.getElementById('newImagePreview');
    preview.innerHTML='';
    [...this.files].forEach(file=>{
        const url = URL.createObjectURL(file);
        const col = document.createElement('div');
        col.className='col-4';
        col.innerHTML=`<img class="preview-img rounded-3 border" src="${url}" alt="preview">`;
        preview.appendChild(col);
    });
});
</script>
@endsection
