@extends('layouts.app')
@section('content')
@php $canReceipt=in_array($order->status,['paid','preparing','packed','shipped','delivered']) || in_array($order->payment_status,['approved','paid']); @endphp
<div class="container">
    <div class="bg-white rounded-4 shadow-sm p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div><h2 class="fw-bold mb-1">{{$order->order_number}}</h2><div class="text-muted">{{$order->created_at?->format('d/m/Y H:i')}}</div></div>
            <div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-brand rounded-pill" href="{{route('member.orders')}}">{{__('Back to Orders')}}</a>@if($canReceipt)<a class="btn btn-brand rounded-pill" href="{{route('receipts.show',$order)}}"><i class="bi bi-receipt"></i> {{__('Receipt')}}</a>@else<span class="btn btn-outline-secondary rounded-pill disabled">{{__('Receipt not available yet')}}</span>@endif</div>
        </div>
        <div class="d-flex gap-2 mb-4 flex-wrap"><span class="badge rounded-pill {{$order->status_badge_class}}">{{$order->status_label}}</span><span class="badge rounded-pill {{$order->payment_status_badge_class}}">{{$order->payment_status_label}}</span></div>
        <div class="table-responsive"><table class="table align-middle"><thead class="table-light"><tr><th>{{__('Products')}}</th><th>SKU</th><th class="text-center">{{__('Quantity')}}</th><th class="text-end">{{__('Total')}}</th></tr></thead><tbody>@foreach($order->items as $i)<tr><td>{{$i->product?->display_name ?? $i->product_name}}</td><td class="text-muted">{{$i->product->sku ?? '-'}}</td><td class="text-center">{{$i->quantity}}</td><td class="text-end fw-bold text-danger">฿{{number_format($i->total,2)}}</td></tr>@endforeach</tbody></table></div>
    </div>
</div>
@endsection
