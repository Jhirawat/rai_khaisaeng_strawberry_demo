@extends('layouts.admin')
@section('title','คำสั่งซื้อ')
@section('content')
@php
$statusLabels=\App\Models\Order::statusLabels();
$statusClass=[
    'pending_payment'=>'warning','confirmed'=>'primary','paid'=>'success','preparing'=>'info','packed'=>'secondary',
    'shipped'=>'primary','delivered'=>'success','delivery_failed'=>'danger','cancelled'=>'dark'
];
$current=request('status');
@endphp
<div class="order-status-summary mb-4">
    <div class="status-card"><div class="text-muted small">ออเดอร์ทั้งหมด</div><h3>{{number_format($summary['total'] ?? $orders->total())}}</h3></div>
    <div class="status-card warning"><div class="text-muted small">รอชำระเงิน</div><h3>{{number_format($summary['pending'] ?? 0)}}</h3></div>
    <div class="status-card primary"><div class="text-muted small">ยืนยันแล้ว</div><h3>{{number_format($summary['confirmed'] ?? 0)}}</h3></div>
    <div class="status-card success"><div class="text-muted small">ชำระเงินแล้ว</div><h3>{{number_format($summary['paid'] ?? 0)}}</h3></div>
    <div class="status-card info"><div class="text-muted small">เตรียมสินค้า</div><h3>{{number_format($summary['preparing'] ?? 0)}}</h3></div>
    <div class="status-card secondary"><div class="text-muted small">จัดเสร็จแล้ว</div><h3>{{number_format($summary['packed'] ?? 0)}}</h3></div>
    <div class="status-card primary"><div class="text-muted small">กำลังจัดส่ง</div><h3>{{number_format($summary['shipping'] ?? 0)}}</h3></div>
    <div class="status-card success"><div class="text-muted small">จัดส่งแล้ว</div><h3>{{number_format($summary['delivered'] ?? 0)}}</h3></div>
    <div class="status-card danger"><div class="text-muted small">จัดส่งไม่สำเร็จ</div><h3>{{number_format($summary['delivery_failed'] ?? 0)}}</h3></div>
    <div class="status-card dark"><div class="text-muted small">ยกเลิก</div><h3>{{number_format($summary['cancelled'] ?? 0)}}</h3></div>
    <div class="status-card sales"><div class="text-muted small">ยอดรวม</div><h3>฿{{number_format($summary['sales'] ?? 0,2)}}</h3></div>
</div>
<div class="content-card p-4 mb-4">
    <div class="mb-3"><div class="fw-bold mb-2"><i class="bi bi-funnel"></i> แถบสถานะออเดอร์</div>
        <div class="status-tabs d-flex flex-wrap gap-2">
            <a class="btn btn-sm rounded-pill {{$current===''||$current===null?'btn-danger':'btn-outline-danger'}}" href="{{route('admin.orders.index')}}">ทั้งหมด</a>
            @foreach($statusLabels as $key=>$label)
                @php $countKey = $key === 'pending_payment' ? 'pending' : ($key === 'shipped' ? 'shipping' : $key); @endphp
                <a class="btn btn-sm rounded-pill {{$current===$key?'btn-danger':'btn-outline-secondary'}}" href="{{route('admin.orders.index',['status'=>$key])}}">{{$label}}</a>
            @endforeach
        </div>
    </div>
    <form class="row g-2 align-items-end" method="get">
        @if($current)<input type="hidden" name="status" value="{{$current}}">@endif
        <div class="col-lg-6"><label class="form-label small text-muted">ค้นหา</label><input class="form-control rounded-pill" name="search" value="{{request('search')}}" placeholder="ค้นหาเลขออเดอร์ / ชื่อลูกค้า / อีเมล"></div>
        <div class="col-lg-4"><label class="form-label small text-muted">วันที่</label><input class="form-control rounded-pill" type="date" name="date" value="{{request('date')}}"></div>
        <div class="col-lg-2"><button class="btn btn-danger rounded-pill w-100">ค้นหา</button></div>
    </form>
</div>
<form method="post" action="{{route('admin.orders.bulkUpdate')}}" class="preserve-scroll-form" data-scroll-key="admin-orders-scroll" id="orderBulkForm">
@csrf @method('patch')
<div class="d-flex justify-content-end align-items-center gap-2 mb-3">
    <span class="text-muted fw-bold" id="orderDirtyStatus">ยังไม่มีรายการที่แก้ไข</span>
    <button class="btn btn-danger rounded-pill px-4 fw-bold" id="saveOrderChanges" disabled><i class="bi bi-save"></i> บันทึกรายการที่แก้</button>
</div>
<div class="table-responsive content-card p-0">
<table class="table table-hover align-middle mb-0 order-table">
<thead class="table-light"><tr><th>วันที่</th><th>เลขออเดอร์</th><th>ลูกค้า</th><th>ยอดเงิน</th><th>ชำระเงิน</th><th>จัดส่ง</th><th>สถานะ</th><th>เปลี่ยนสถานะ</th><th class="text-end">จัดการ</th></tr></thead>
<tbody>
@forelse($orders as $o)
@php
$options=$statusOptions[$o->id] ?? [$o->status];
$hasStatusTargets=count($options)>1;
@endphp
<tr>
    <td class="text-nowrap">{{optional($o->ordered_at ?? $o->created_at)->format('d/m/Y H:i')}}</td>
    <td class="text-nowrap fw-bold"><a href="{{route('admin.orders.show',$o)}}">{{$o->order_number}}</a></td>
    <td class="text-nowrap">{{$o->user->name ?? '-'}}</td>
    <td class="text-nowrap fw-bold text-danger">฿{{number_format($o->total,2)}}</td>
    <td class="text-nowrap"><span class="badge bg-light text-dark border">{{$o->payment->method ?? '-'}}</span> <span class="badge bg-secondary">{{$o->payment->status ?? $o->payment_status}}</span></td>
    <td class="text-nowrap">{{$o->shipment->tracking_number ? 'Tracking: '.$o->shipment->tracking_number : '-'}}</td>
    <td class="text-nowrap"><span class="badge bg-{{$statusClass[$o->status] ?? 'secondary'}}">{{$statusLabels[$o->status] ?? $o->status}}</span></td>
    <td class="text-nowrap">
        <select name="statuses[{{$o->id}}]" class="form-select form-select-sm rounded-pill order-status-select" data-original="{{$o->status}}" @disabled(!$hasStatusTargets)>
            @foreach($options as $key)<option value="{{$key}}" @selected($o->status===$key)>{{$statusLabels[$key] ?? $key}}</option>@endforeach
        </select>
    </td>
    <td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-danger rounded-pill px-3" href="{{route('admin.orders.show',$o)}}">รายละเอียด</a></td>
</tr>
@empty
<tr><td colspan="9" class="text-center text-muted py-4">ไม่พบคำสั่งซื้อ</td></tr>
@endforelse
</tbody>
</table>
</div>
</form>
@if($orders->hasPages())<div class="mt-3 order-pagination">{{$orders->links('pagination::bootstrap-5')}}</div>@endif
<style>
.order-status-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px}.status-card{background:#fff;border-radius:18px;padding:16px 18px;box-shadow:0 8px 30px rgba(0,0,0,.06);border-left:5px solid #e9ecef}.status-card h3{font-weight:900;margin:0;color:#111827}.status-card.warning{border-color:#ffc107}.status-card.success{border-color:#198754}.status-card.info{border-color:#0dcaf0}.status-card.secondary{border-color:#6c757d}.status-card.primary{border-color:#0d6efd}.status-card.danger{border-color:#dc3545}.status-card.dark{border-color:#212529}.status-card.sales{border-color:#dc2f43}.status-card.sales h3{color:#dc2f43}.order-table th,.order-table td{white-space:nowrap}.order-pagination svg{width:16px!important;height:16px!important}.status-tabs .btn{font-weight:700}.order-status-select{min-width:150px}.order-table tbody tr:hover{background:#fff7f8}
</style>
<script>
(function(){
  const key='admin-orders-scroll';
  const saved=sessionStorage.getItem(key);
  if(saved){ setTimeout(()=>{window.scrollTo(0, parseInt(saved,10)||0); sessionStorage.removeItem(key);}, 80); }
document.querySelectorAll('.preserve-scroll-form').forEach(form=>{
    form.addEventListener('submit',()=>sessionStorage.setItem(form.dataset.scrollKey||key, String(window.scrollY)));
  });
})();
const orderSelects=[...document.querySelectorAll('.order-status-select')];
const saveOrderChanges=document.getElementById('saveOrderChanges');
const orderDirtyStatus=document.getElementById('orderDirtyStatus');
function syncOrderChanges(){
    const changed=orderSelects.filter(select=>!select.disabled && select.value!==select.dataset.original);
    saveOrderChanges.disabled=changed.length===0;
    orderDirtyStatus.textContent=changed.length ? `แก้ไขแล้ว ${changed.length} รายการ` : 'ยังไม่มีรายการที่แก้ไข';
}
orderSelects.forEach(select=>select.addEventListener('change',syncOrderChanges));
document.getElementById('orderBulkForm')?.addEventListener('submit',event=>{
    if(saveOrderChanges.disabled){ event.preventDefault(); return; }
    orderSelects.forEach(select=>{if(!select.disabled && select.value===select.dataset.original) select.disabled=true;});
});
</script>
@endsection
