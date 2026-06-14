@extends('layouts.admin')
@section('title','หมวดหมู่สินค้า')
@section('content')
<div class="content-card p-4 mb-4 category-hero">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="text-muted small mb-1">หน้าหลัก &gt; จัดการสินค้า &gt; หมวดหมู่</div>
            <h3 class="fw-bold mb-1">หมวดหมู่สินค้า</h3>
            <p class="text-muted mb-0">จัดกลุ่มสินค้าเพื่อให้ลูกค้าค้นหาและเลือกซื้อได้ง่ายขึ้น</p>
        </div>
        <div class="category-stat"><span>{{$categories->total()}}</span><small>หมวดหมู่</small></div>
    </div>
</div>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="content-card p-4 h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle text-danger"></i> เพิ่มหมวดหมู่</h5>
            <form method="post" action="{{route('admin.categories.store')}}">@csrf
                <label class="form-label">ชื่อหมวดหมู่ (ไทย)</label>
                <input name="name" class="form-control rounded-pill mb-3" placeholder="เช่น สตรอว์เบอร์รีสด" required>
                <label class="form-label">Category Name (English)</label>
                <input name="name_en" class="form-control rounded-pill mb-3" placeholder="Fresh Strawberry">
                <label class="form-label">รายละเอียด</label>
                <textarea name="description" rows="3" class="form-control rounded-4 mb-3" placeholder="รายละเอียดหมวดหมู่"></textarea>
                <button class="btn btn-danger rounded-pill w-100">เพิ่มหมวดหมู่</button>
            </form>
            <hr>
            <div class="small text-muted">Slug ใช้สำหรับ URL เช่น <code>strawberry-fresh</code> ระบบจะสร้างให้อัตโนมัติเป็นตัวอักษร</div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="content-card p-0 overflow-hidden">
            <div class="table-responsive">
                <table class="table align-middle mb-0 category-table">
                    <thead class="table-light"><tr><th>หมวดหมู่</th><th>Slug</th><th>สินค้า</th><th>ลิงก์หน้าร้าน</th><th class="text-end">จัดการ</th></tr></thead>
                    <tbody>
                    @forelse($categories as $c)
                        <tr>
                            <td>
                                <form id="categoryUpdate{{$c->id}}" method="post" action="{{route('admin.categories.update',$c)}}" class="category-edit-form">@csrf @method('put')
                                    <input name="name" class="form-control rounded-pill mb-2 fw-bold" value="{{$c->name}}" required>
                                    <input name="name_en" class="form-control rounded-pill mb-2" value="{{$c->name_en}}" placeholder="English name">
                                    <textarea name="description" class="form-control rounded-4" rows="2" placeholder="รายละเอียด">{{$c->description}}</textarea>
                                </form>
                            </td>
                            <td><code>{{$c->slug}}</code></td>
                            <td><span class="badge rounded-pill bg-danger-subtle text-danger">{{$c->products_count}} รายการ</span></td>
                            <td><a class="btn btn-sm btn-outline-danger rounded-pill" target="_blank" href="{{route('shop.products',['category'=>$c->slug])}}">ดูสินค้า</a></td>
                            <td class="text-end text-nowrap">
                                <button form="categoryUpdate{{$c->id}}" class="btn btn-sm btn-outline-primary rounded-pill px-3">บันทึก</button>
                                <form method="post" action="{{route('admin.categories.destroy',$c)}}" class="d-inline" onsubmit="return confirm('ยืนยันลบหมวดหมู่นี้?')">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger rounded-pill px-3">ลบ</button></form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">ยังไม่มีหมวดหมู่</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-3">{{$categories->links('pagination::bootstrap-5')}}</div>
    </div>
</div>
<style>
.category-hero{background:linear-gradient(135deg,#fff,#ffe9ed)}.category-stat{background:#dc2f43;color:#fff;border-radius:24px;padding:12px 22px;text-align:center}.category-stat span{display:block;font-size:32px;font-weight:800}.category-stat small{font-weight:700}.category-table td,.category-table th{padding:18px 20px;vertical-align:middle}.category-table tbody tr:hover{background:#fff7f8}.category-edit-form{min-width:260px}
</style>
@endsection
