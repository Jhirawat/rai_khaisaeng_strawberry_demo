@extends('layouts.admin')
@section('title','รายละเอียดคำสั่งซื้อ')
@section('content')
@php
$statusLabels=\App\Models\Order::statusLabels();
$hasStatusTargets=count($statusOptions)>1;
$address=is_array($order->shipping_address_snapshot)?$order->shipping_address_snapshot:json_decode($order->shipping_address_snapshot ?? '[]',true);
$canReceipt=$order->canIssueReceipt();
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div><h4 class="fw-bold mb-1">{{$order->order_number}}</h4><div class="text-muted">วันที่สั่งซื้อ {{optional($order->ordered_at ?? $order->created_at)->format('d/m/Y H:i')}}</div></div>
    <div class="d-flex flex-wrap gap-2">@if($canReceipt)<a href="{{route('receipts.show',$order)}}" class="btn btn-outline-success rounded-pill"><i class="bi bi-receipt"></i> ใบเสร็จ</a><a href="{{route('receipts.download',$order)}}" class="btn btn-success rounded-pill"><i class="bi bi-file-earmark-pdf"></i> ดาวน์โหลด PDF</a>@else<span class="btn btn-outline-secondary rounded-pill disabled"><i class="bi bi-clock"></i> ใบเสร็จยังไม่พร้อม</span>@endif<button onclick="window.print()" class="btn btn-outline-danger rounded-pill"><i class="bi bi-printer"></i> พิมพ์รายละเอียด</button></div>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="content-card p-4 mb-3">
            <div class="d-flex flex-wrap justify-content-between gap-3 align-items-center">
                <div><div class="text-muted small">สถานะปัจจุบัน</div><span class="badge {{$order->status_badge_class}} fs-6 rounded-pill px-3 py-2">{{$statusLabels[$order->status] ?? $order->status}}</span></div>
                <form method="post" action="{{route('admin.orders.update',$order)}}" class="d-flex flex-wrap gap-2">@csrf @method('patch')
                    <select name="status" class="form-select rounded-pill" style="min-width:230px" @disabled(!$hasStatusTargets)>
                        @foreach($statusOptions as $key)<option value="{{$key}}" @selected($order->status===$key)>{{$statusLabels[$key] ?? $key}}</option>@endforeach
                    </select>
                    <button class="btn btn-danger rounded-pill px-4" @disabled(!$hasStatusTargets)>เปลี่ยนสถานะ</button>
                </form>
            </div>
            @if(!$hasStatusTargets)<div class="alert alert-secondary mt-3 mb-0">ไม่มีการเปลี่ยนสถานะทั่วไปที่ใช้ได้ กรุณาดูการจัดส่งด้านล่าง</div>@endif
        </div>
        @if($order->shipment && count($shipmentOptions))
        <div class="content-card p-4 mb-3" id="shipping-workflow">
            <h5 class="fw-bold">การจัดส่ง / Shipping</h5>
            <p class="text-muted">เตรียมสินค้า → จัดเสร็จแล้ว (เปลี่ยนสถานะด้านบน) → จัดส่ง → ส่งสำเร็จหรือส่งคืน</p>
            @if($order->payment?->method === 'cod')<p>COD: ส่งสำเร็จยืนยันเก็บเงินแล้ว ส่งคืนบันทึกว่ายังไม่ได้รับเงิน และส่งใหม่ได้</p>@endif
            <form method="post" action="{{route('admin.shipping.update',$order->shipment)}}" class="row g-2">
                @csrf @method('patch')
                <div class="col-md-6"><label for="shipment-carrier" class="form-label">ขนส่ง / Carrier</label><input id="shipment-carrier" name="carrier" class="form-control" maxlength="255" value="{{old('carrier',$order->shipment->carrier)}}"></div>
                <div class="col-md-6"><label for="shipment-tracking" class="form-label">เลขติดตาม / Tracking</label><input id="shipment-tracking" name="tracking_number" class="form-control" maxlength="255" value="{{old('tracking_number',$order->shipment->tracking_number)}}"></div>
                <div class="col-md-8"><label for="shipment-status" class="form-label">สถานะจัดส่ง / Shipment status</label><select id="shipment-status" name="status" class="form-select">
                    @foreach($shipmentOptions as $key)<option value="{{$key}}" @selected($order->shipment->status===$key)>{{$statusLabels[$key === 'returned' ? 'delivery_failed' : $key] ?? $key}}</option>@endforeach
                </select></div>
                <div class="col-md-4 align-self-end"><button class="btn btn-success">บันทึกการจัดส่ง / Save shipping</button></div>
            </form>
        </div>
        @endif
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
        <div class="content-card p-4"><h5 class="fw-bold">ข้อมูลชำระเงิน</h5>@if($payment)<p>วิธี: <b>{{['qr'=>'QR Code','bank_transfer'=>'โอนธนาคาร','cod'=>'เก็บเงินปลายทาง'][$payment->method] ?? $payment->method}}</b></p><p>สถานะ: <b>{{$payment->status}}</b></p>@if($payment->slip_path)<a target="_blank" rel="noopener noreferrer" href="{{route('admin.payments.slip',$payment)}}"><img src="{{route('admin.payments.slip',$payment)}}" class="img-fluid rounded border"></a>@else<p class="text-muted">ไม่มีสลิป</p>@endif @else <p class="text-muted">ยังไม่มีข้อมูลชำระเงิน</p>@endif</div>
    </div>
</div>
<style>@media print{.admin-sidebar,.admin-topbar,.btn{display:none!important}main{padding:0!important}.content-card{box-shadow:none!important;border:1px solid #ddd}.timeline{padding-left:1.2rem}}</style>
@endsection
