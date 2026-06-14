@extends('layouts.admin')
@section('title','รายละเอียดคำสั่งซื้อ')
@section('content')
@php
$statusLabels=['pending_payment'=>'รอชำระเงิน','paid'=>'ชำระเงินแล้ว','preparing'=>'เตรียมสินค้า','packed'=>'จัดเสร็จแล้ว','shipped'=>'กำลังจัดส่ง','delivered'=>'จัดส่งแล้ว','delivery_failed'=>'จัดส่งไม่สำเร็จ','cancelled'=>'ยกเลิก'];
$address=is_array($order->shipping_address_snapshot)?$order->shipping_address_snapshot:json_decode($order->shipping_address_snapshot ?? '[]',true);
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h4 class="fw-bold mb-1">{{$order->order_number}}</h4><div class="text-muted">วันที่สั่งซื้อ {{optional($order->ordered_at ?? $order->created_at)->format('d/m/Y H:i')}}</div></div>
    <div class="d-flex flex-wrap gap-2"><a href="{{route('receipts.show',$order)}}" class="btn btn-outline-success rounded-pill"><i class="bi bi-receipt"></i> ใบเสร็จ</a><a href="{{route('receipts.download',$order)}}" class="btn btn-success rounded-pill"><i class="bi bi-file-earmark-pdf"></i> ดาวน์โหลด PDF</a><button onclick="window.print()" class="btn btn-danger rounded-pill"><i class="bi bi-truck"></i> พิมพ์ใบปะหน้า</button></div>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="content-card p-4 mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
                <div><div class="text-muted small">สถานะปัจจุบัน</div><span class="badge bg-danger fs-6 rounded-pill px-3 py-2">{{$statusLabels[$order->status] ?? $order->status}}</span></div>
                <form method="post" action="{{route('admin.orders.update',$order)}}" class="d-flex flex-wrap gap-2">@csrf @method('patch')
                    <select name="status" class="form-select rounded-pill" style="min-width:230px">
                        @foreach($statusLabels as $key=>$label)<option value="{{$key}}" @selected($order->status===$key)>{{$label}}</option>@endforeach
                    </select>
                    <button class="btn btn-danger rounded-pill px-4">เปลี่ยนสถานะ</button>
                </form>
            </div>
        </div>
        <div class="table-responsive content-card p-0 mb-3">
            <table class="table align-middle mb-0"><thead class="table-light"><tr><th>SKU</th><th>สินค้า</th><th class="text-center">จำนวน</th><th class="text-end">ราคาต่อชิ้น</th><th class="text-end">รวม</th></tr></thead><tbody>
            @foreach($order->items as $i)<tr><td class="text-muted">{{$i->product->sku ?? '-'}}</td><td class="fw-bold">{{$i->product_name}}</td><td class="text-center">{{$i->quantity}}</td><td class="text-end">฿{{number_format($i->price,2)}}</td><td class="text-end text-danger fw-bold">฿{{number_format($i->total,2)}}</td></tr>@endforeach
            </tbody></table>
        </div>
        <div class="content-card p-4"><h5 class="fw-bold mb-3">ประวัติการทำรายการ</h5><ul class="timeline mb-0"><li>สร้างคำสั่งซื้อ: {{optional($order->created_at)->format('d/m/Y H:i')}}</li><li>สถานะชำระเงิน: {{$order->payment->status ?? $order->payment_status}}</li><li>สถานะจัดส่ง: {{$order->shipment->status ?? '-'}}</li></ul></div>
    </div>
    <div class="col-lg-4">
        <div class="content-card p-4 mb-3"><h5 class="fw-bold">ข้อมูลลูกค้าและที่อยู่</h5><p class="mb-1"><b>{{$order->user->name ?? '-'}}</b></p><p class="mb-1">โทร: {{$address['phone'] ?? $order->user->phone ?? '-'}}</p><p class="text-muted mb-0">{{$address['address'] ?? '-'}} {{$address['subdistrict'] ?? ''}} {{$address['district'] ?? ''}} {{$address['province'] ?? ''}} {{$address['postal_code'] ?? ''}}</p></div>
        <div class="content-card p-4 mb-3"><h5 class="fw-bold">สรุปยอด</h5><div class="d-flex justify-content-between"><span>สินค้า</span><b>฿{{number_format($order->subtotal,2)}}</b></div><div class="d-flex justify-content-between"><span>จัดส่ง</span><b>฿{{number_format($order->shipping_fee,2)}}</b></div><hr><div class="d-flex justify-content-between fs-5"><span>รวม</span><b class="text-danger">฿{{number_format($order->total,2)}}</b></div></div>
        @php $payment=$order->payment ?? null; @endphp
        <div class="content-card p-4"><h5 class="fw-bold">ข้อมูลชำระเงิน</h5>@if($payment)<p>วิธี: <b>{{['qr'=>'QR Code','bank_transfer'=>'โอนธนาคาร','cod'=>'เก็บเงินปลายทาง'][$payment->method] ?? $payment->method}}</b></p><p>สถานะ: <b>{{$payment->status}}</b></p>@if($payment->slip_path)<a target="_blank" href="{{asset('storage/'.$payment->slip_path)}}"><img src="{{asset('storage/'.$payment->slip_path)}}" class="img-fluid rounded border"></a>@else<p class="text-muted">ไม่มีสลิป</p>@endif @else <p class="text-muted">ยังไม่มีข้อมูลชำระเงิน</p>@endif</div>
    </div>
</div>
<style>@media print{.admin-sidebar,.admin-topbar,.btn{display:none!important}main{padding:0!important}.content-card{box-shadow:none!important;border:1px solid #ddd}.timeline{padding-left:1.2rem}}</style>
@endsection
