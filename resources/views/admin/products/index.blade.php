@extends('layouts.admin')
@section('title','จัดการสินค้า')
@section('content')
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
    <div>
        <h1 class="fw-bold mb-1">จัดการสินค้า</h1>
        <div class="text-muted">ค้นหา กรอง ตรวจสอบราคา สต๊อก และจัดการสินค้าได้จากหน้าเดียว</div>
    </div>
    <a class="btn btn-danger rounded-pill px-4 fw-bold" href="{{route('admin.products.create')}}"><i class="bi bi-plus-circle"></i> เพิ่มสินค้าใหม่</a>
</div>

<div class="content-card p-4 mb-4">
    <form class="row g-3 align-items-end" method="get">
        <div class="col-lg-4"><label class="form-label small text-muted">ค้นหาสินค้า / SKU</label><input class="form-control rounded-pill" name="search" value="{{request('search')}}" placeholder="ชื่อสินค้า, SKU, รายละเอียด"></div>
        <div class="col-lg-2"><label class="form-label small text-muted">หมวดหมู่</label><select class="form-select rounded-pill" name="category_id"><option value="">ทุกหมวดหมู่</option>@foreach($categories as $c)<option value="{{$c->id}}" @selected(request('category_id')==$c->id)>{{$c->name}}</option>@endforeach</select></div>
        <div class="col-lg-2"><label class="form-label small text-muted">สถานะการขาย</label><select class="form-select rounded-pill" name="status"><option value="">ทุกสถานะ</option><option value="active" @selected(request('status')==='active')>พร้อมขาย</option><option value="inactive" @selected(request('status')==='inactive')>ปิดการขาย</option></select></div>
        <div class="col-lg-2"><label class="form-label small text-muted">คลังสินค้า</label><select class="form-select rounded-pill" name="stock"><option value="">ทั้งหมด</option><option value="low" @selected(request('stock')==='low')>ใกล้หมด</option><option value="out" @selected(request('stock')==='out')>หมด</option></select></div>
        <div class="col-lg-1"><label class="form-label small text-muted">แถว</label><select class="form-select rounded-pill" name="per_page"><option value="20" @selected(request('per_page',20)==20)>20</option><option value="50" @selected(request('per_page')==50)>50</option><option value="100" @selected(request('per_page')==100)>100</option></select></div>
        <div class="col-lg-1"><button class="btn btn-danger rounded-pill w-100">ค้นหา</button></div>
    </form>
</div>

<form method="post" action="{{route('admin.products.bulk')}}" id="bulkProductForm" class="preserve-scroll-form" data-scroll-key="admin-products-scroll">
@csrf
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex gap-2 align-items-center">
        <select name="action" class="form-select rounded-pill" style="min-width:210px">
            <option value="">Bulk Actions</option>
            <option value="activate">เปิดการขายสินค้าที่เลือก</option>
            <option value="deactivate">ปิดการขายสินค้าที่เลือก</option>
            <option value="delete">ลบสินค้าที่เลือก</option>
        </select>
        <button class="btn btn-outline-danger rounded-pill px-4" onclick="return confirm('ยืนยันดำเนินการกับสินค้าที่เลือก?')">ดำเนินการ</button>
    </div>
    <div class="text-muted small">แสดง {{$products->count()}} รายการ จากทั้งหมด {{$products->total()}} รายการ</div>
</div>

<div class="table-responsive content-card p-0">
<table class="table table-hover align-middle mb-0 admin-product-table">
<thead class="table-light"><tr><th><input type="checkbox" id="checkAllProducts"></th><th>รูป</th><th>ชื่อสินค้า / SKU</th><th>หมวดหมู่</th><th>ราคา</th><th>สต๊อก</th><th>สถานะสต๊อก</th><th>สถานะการขาย</th><th class="text-end">จัดการ</th></tr></thead>
<tbody>
@forelse($products as $p)
@php
    $img = $p->images->firstWhere('is_primary', true) ?? $p->images->first();
    $stock = $p->inventory->quantity ?? 0;
    $threshold = $p->inventory->low_stock_threshold ?? 10;
    $stockStatus = $stock <= 0 ? ['หมด','danger'] : ($stock <= $threshold ? ['ใกล้หมด','warning'] : ['ปกติ','success']);
@endphp
<tr>
    <td><input type="checkbox" name="ids[]" value="{{$p->id}}" class="product-check"></td>
    <td style="width:90px">
        @if($img)
            <img src="{{$img->url}}" class="rounded-3 border product-thumb" alt="{{$p->name}}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
            <div class="no-img rounded-3" style="display:none"><i class="bi bi-image"></i></div>
        @else
            <div class="no-img rounded-3"><i class="bi bi-image"></i></div>
        @endif
    </td>
    <td><div class="fw-bold product-name">{{$p->name}}</div><div class="text-muted small">SKU: {{$p->sku}}</div></td>
    <td class="text-nowrap">{{$p->category->name ?? '-'}}</td>
    <td class="text-nowrap fw-bold text-danger">฿{{number_format($p->price,2)}}</td>
    <td class="text-nowrap fw-bold">{{number_format($stock)}} ชิ้น</td>
    <td class="text-nowrap"><span class="badge rounded-pill bg-{{$stockStatus[1]}}">{{$stockStatus[0]}}</span><div class="small text-muted">ขั้นต่ำ: {{$threshold}}</div></td>
    <td class="text-nowrap"><span class="badge rounded-pill {{$p->status==='active'?'bg-success':'bg-secondary'}}">{{$p->status==='active'?'พร้อมขาย':'ปิดการขาย'}}</span></td>
    <td class="text-end text-nowrap">
        <a class="btn btn-sm btn-outline-primary rounded-pill px-3 product-edit-link" href="{{route('admin.products.edit',$p)}}">แก้ไข</a>
        <form method="post" action="{{route('admin.products.destroy',$p)}}" class="d-inline" onsubmit="return confirm('ยืนยันลบสินค้า?')">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger rounded-pill px-3">ลบ</button></form>
    </td>
</tr>
@empty
<tr><td colspan="9" class="text-center text-muted py-5">ไม่พบสินค้า</td></tr>
@endforelse
</tbody>
</table>
</div>
</form>
@if($products->hasPages())<div class="mt-3 product-pagination">{{$products->links('pagination::bootstrap-5')}}</div>@endif
<style>
.admin-product-table th,.admin-product-table td{white-space:nowrap}.admin-product-table td:nth-child(3){min-width:240px}.product-thumb{width:72px;height:58px;object-fit:cover}.no-img{width:72px;height:58px;background:#fff1f3;color:#dc2f43;align-items:center;justify-content:center}.product-name{white-space:normal;max-width:300px}.product-pagination svg{width:16px!important;height:16px!important}.product-pagination .page-link{border-radius:10px;margin:0 .15rem;color:#dc2f43}.product-pagination .active .page-link{background:#dc2f43;border-color:#dc2f43;color:#fff}
</style>
<script>
(function(){
  const key='admin-products-scroll';
  const saved=sessionStorage.getItem(key);
  if(saved){ setTimeout(()=>{window.scrollTo(0, parseInt(saved,10)||0); sessionStorage.removeItem(key);}, 80); }
  document.querySelectorAll('.preserve-scroll-form').forEach(form=>form.addEventListener('submit',()=>sessionStorage.setItem(form.dataset.scrollKey||key, String(window.scrollY))));
  document.querySelectorAll('.product-edit-link').forEach(a=>a.addEventListener('click',()=>sessionStorage.setItem(key, String(window.scrollY))));
})();
document.getElementById('checkAllProducts')?.addEventListener('change',e=>document.querySelectorAll('.product-check').forEach(c=>c.checked=e.target.checked));
document.getElementById('bulkProductForm')?.addEventListener('submit',e=>{const action=e.target.querySelector('[name="action"]').value; const checked=document.querySelectorAll('.product-check:checked').length; if(!action||!checked){e.preventDefault(); alert('กรุณาเลือกสินค้าและเลือก Bulk Action');}});
</script>
@endsection
