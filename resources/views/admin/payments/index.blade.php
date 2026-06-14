@extends('layouts.admin')
@section('title','ตรวจสอบชำระเงิน')
@section('content')
<div class="table-responsive bg-white rounded shadow-sm">
<table class="table align-middle mb-0">
    <thead class="table-light"><tr><th>Order</th><th>ลูกค้า</th><th>วิธีชำระ</th><th>ยอด</th><th>สลิป</th><th>ตรวจสลิป OCR</th><th>สถานะ</th><th class="text-end">จัดการ</th></tr></thead>
    <tbody>
    @foreach($payments as $p)
    <tr>
        <td><a href="{{route('admin.orders.show',$p->order)}}" class="fw-bold">{{$p->order->order_number}}</a></td>
        <td>{{$p->order->user->name ?? '-'}}</td>
        <td><span class="badge bg-info">{{['qr'=>'QR Code','bank_transfer'=>'โอนธนาคาร','cod'=>'เก็บเงินปลายทาง'][$p->method] ?? $p->method}}</span></td>
        <td class="fw-bold text-danger">฿{{number_format($p->amount,2)}}</td>
        <td>@if($p->slip_path)<a target="_blank" href="{{asset('storage/'.$p->slip_path)}}"><img src="{{asset('storage/'.$p->slip_path)}}" style="width:80px;height:80px;object-fit:cover" class="rounded border"></a>@else<span class="text-muted">ไม่มีสลิป</span>@endif</td>
        <td>
            @php($ocrStatus = $p->slip_review_status ?? 'needs_review')
            @if($ocrStatus === 'verified_by_qr')
                <span class="badge bg-success">ผ่าน QR</span>
            @elseif($ocrStatus === 'verified_by_ocr')
                <span class="badge bg-success">ผ่าน OCR</span>
            @elseif($ocrStatus === 'invalid')
                <span class="badge bg-danger">ไม่คล้ายสลิป</span>
            @elseif($ocrStatus === 'not_required')
                <span class="badge bg-secondary">ไม่ต้องมีสลิป</span>
            @else
                <span class="badge bg-warning text-dark">ต้องตรวจสอบ</span>
            @endif
            @if(($p->slip_ocr_score ?? 0) > 0)<div class="small text-muted">คะแนน: {{$p->slip_ocr_score}}</div>@endif
            @if($p->slip_ocr_note)<div class="small text-muted">{{$p->slip_ocr_note}}</div>@endif
        </td>
        <td><span class="badge bg-{{$p->status==='approved'?'success':($p->status==='rejected'?'danger':'warning')}}">{{$p->status}}</span></td>
        <td class="text-end">
            @if($p->status==='pending')
            <form class="d-inline" method="post" action="{{route('admin.payments.approve',$p)}}">@csrf<button class="btn btn-success btn-sm rounded-pill">ยืนยันชำระสำเร็จ</button></form>
            <form class="d-inline" method="post" action="{{route('admin.payments.reject',$p)}}">@csrf<button class="btn btn-danger btn-sm rounded-pill">ปฏิเสธ</button></form>
            @else
            <a class="btn btn-outline-success btn-sm rounded-pill" href="{{route('receipts.show',$p->order)}}"><i class="bi bi-receipt"></i> ใบเสร็จ</a>
            @endif
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
</div>
@endsection
