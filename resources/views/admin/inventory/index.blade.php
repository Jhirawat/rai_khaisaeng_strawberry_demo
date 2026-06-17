@extends('layouts.admin')
@section('title','คลังสินค้า')
@section('content')
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="content-card p-3"><div class="text-muted small">รายการสินค้า</div><h3 class="fw-bold mb-0">{{number_format($summary['total'])}}</h3></div></div>
    <div class="col-md-3"><a class="text-decoration-none text-dark" href="{{route('admin.inventory.index',['low_stock'=>1])}}"><div class="content-card p-3 border border-warning-subtle"><div class="text-muted small">สินค้าใกล้หมด</div><h3 class="fw-bold text-warning mb-0">{{number_format($summary['low'])}}</h3><small class="text-muted">กดดูรายการ</small></div></a></div>
    <div class="col-md-3"><div class="content-card p-3"><div class="text-muted small">สินค้าหมด</div><h3 class="fw-bold text-danger mb-0">{{number_format($summary['out'])}}</h3></div></div>
    <div class="col-md-3"><div class="content-card p-3"><div class="text-muted small">จำนวนรวมในคลัง</div><h3 class="fw-bold text-success mb-0">{{number_format($summary['stock'])}}</h3></div></div>
</div>
<div class="content-card p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h5 class="fw-bold mb-1">จัดการคลังสินค้า</h5>
            <div class="text-muted small">ปรับจำนวนคงเหลือและจำนวนขั้นต่ำสำหรับแจ้งเตือนสินค้าใกล้หมด</div>
        </div>
        <form class="d-flex gap-2" method="get">
            <input class="form-control rounded-pill" name="search" value="{{request('search')}}" placeholder="ค้นหาสินค้า">
            <button class="btn btn-danger rounded-pill px-4">ค้นหา</button>
            @if(request('low_stock'))<a class="btn btn-outline-secondary rounded-pill" href="{{route('admin.inventory.index')}}">ทั้งหมด</a>@endif
        </form>
    </div>

    <div class="alert alert-light border rounded-4 d-flex flex-wrap gap-3 align-items-center mb-3">
        <div><span class="legend-dot bg-success"></span> <b>ปกติ</b> = สต๊อกมากกว่าขั้นต่ำ</div>
        <div><span class="legend-dot bg-warning"></span> <b>ใกล้หมด</b> = จำนวนคงเหลือน้อยกว่าหรือเท่ากับขั้นต่ำ</div>
        <div><span class="legend-dot bg-danger"></span> <b>หมด</b> = สต๊อกเป็น 0</div>
        <div class="small text-muted w-100">หมายเหตุ: ช่องซ้ายคือ <b>จำนวนคงเหลือในสต๊อก</b> และช่องขวาคือ <b>จำนวนขั้นต่ำที่ใช้แจ้งเตือนสินค้าใกล้หมด</b></div>
    </div>

    <form method="post" action="{{route('admin.inventory.bulkUpdate')}}" class="preserve-scroll-form" data-scroll-key="admin-inventory-scroll">
        @csrf @method('patch')
        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-danger rounded-pill px-4 fw-bold"><i class="bi bi-save"></i> บันทึกทั้งหมด</button>
        </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle inventory-table">
            <thead class="table-light">
                <tr>
                    <th>สินค้า</th>
                    <th>หมวดหมู่</th>
                    <th class="text-center">คงเหลือ</th>
                    <th class="text-center">ขั้นต่ำแจ้งเตือน</th>
                    <th class="text-center">สถานะ</th>
                    <th style="width:420px">ปรับสต๊อก / ขั้นต่ำ</th>
                </tr>
            </thead>
            <tbody>
            @forelse($inventories as $i)
                @php $isLow = $i->quantity <= $i->low_stock_threshold; $isOut = $i->quantity <= 0; @endphp
                <tr class="{{$isOut?'table-danger':($isLow?'table-warning':'')}}">
                    <td><div class="fw-bold">{{$i->product->name}}</div><div class="small text-muted">SKU: {{$i->product->sku}}</div></td>
                    <td>{{$i->product->category->name ?? '-'}}</td>
                    <td class="text-center"><span class="fs-5 fw-bold {{$isLow?'text-danger':'text-success'}}">{{number_format($i->quantity)}}</span></td>
                    <td class="text-center">{{number_format($i->low_stock_threshold)}}</td>
                    <td class="text-center">@if($isOut)<span class="badge bg-danger">หมด</span>@elseif($isLow)<span class="badge bg-warning text-dark">ใกล้หมด</span>@else<span class="badge bg-success">ปกติ</span>@endif</td>
                    <td>
                        <div class="inventory-adjust-form">
                            <div class="adjust-group">
                                <label class="adjust-label">คงเหลือ</label>
                                <button type="button" class="adjust-btn minus" data-target="qty-{{$i->id}}" data-step="-1">−</button>
                                <span class="adjust-value" id="qty-{{$i->id}}-display">{{$i->quantity}}</span>
                                <button type="button" class="adjust-btn plus" data-target="qty-{{$i->id}}" data-step="1">+</button>
                                <input type="hidden" id="qty-{{$i->id}}" name="quantities[{{$i->id}}]" value="{{$i->quantity}}">
                            </div>
                            <div class="adjust-group threshold">
                                <label class="adjust-label">ขั้นต่ำ</label>
                                <button type="button" class="adjust-btn minus" data-target="threshold-{{$i->id}}" data-step="-1">−</button>
                                <span class="adjust-value" id="threshold-{{$i->id}}-display">{{$i->low_stock_threshold}}</span>
                                <button type="button" class="adjust-btn plus" data-target="threshold-{{$i->id}}" data-step="1">+</button>
                                <input type="hidden" id="threshold-{{$i->id}}" name="thresholds[{{$i->id}}]" value="{{$i->low_stock_threshold}}">
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">ไม่พบข้อมูลคลังสินค้า</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    </form>
    <div class="mt-3 inventory-pagination">{{$inventories->links('pagination::bootstrap-5')}}</div>
</div>
<style>
.inventory-table th{font-weight:800}.inventory-table td{padding:1rem .75rem}.legend-dot{display:inline-block;width:12px;height:12px;border-radius:999px;margin-right:4px}.inventory-adjust-form{display:flex;align-items:center;gap:.6rem;flex-wrap:nowrap;white-space:nowrap}.inventory-table th,.inventory-table td{white-space:nowrap}.inventory-table td:first-child{min-width:260px}.inventory-table td:last-child{min-width:420px}.adjust-group{display:inline-flex;align-items:center;border:1px solid #ffd1d8;border-radius:999px;overflow:hidden;background:#fff}.adjust-group.threshold{border-color:#ffe7a3}.adjust-label{font-size:.75rem;font-weight:800;padding:0 .6rem;color:#6c757d}.adjust-btn{width:34px;height:34px;border:0;font-weight:900;background:#fff;color:#dc2f43}.adjust-btn.plus{background:#dc2f43;color:#fff}.adjust-group.threshold .adjust-btn.plus{background:#f6b800;color:#111}.adjust-value{min-width:44px;text-align:center;font-weight:900;color:#dc2f43;background:#fff5f6;padding:.3rem .4rem}.adjust-group.threshold .adjust-value{color:#111;background:#fff9df}
</style>
<script>
(function(){
    const key='admin-inventory-scroll';
    const saved=sessionStorage.getItem(key);
    if(saved){ setTimeout(()=>{window.scrollTo(0, parseInt(saved,10)||0); sessionStorage.removeItem(key);}, 80); }
    document.querySelectorAll('.preserve-scroll-form').forEach(form=>{
        form.addEventListener('submit',()=>sessionStorage.setItem(form.dataset.scrollKey||key, String(window.scrollY)));
    });
})();
document.addEventListener('click',function(e){
    const btn=e.target.closest('.adjust-btn');
    if(!btn) return;
    const input=document.getElementById(btn.dataset.target);
    const display=document.getElementById(btn.dataset.target+'-display');
    if(!input || !display) return;
    const step=parseInt(btn.dataset.step || '0',10);
    const current=parseInt(input.value || '0',10);
    const next=Math.max(0,current+step);
    input.value=next;
    display.textContent=next;
});
</script>
@endsection
