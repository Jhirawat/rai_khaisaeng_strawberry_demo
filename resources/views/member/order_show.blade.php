@extends('layouts.app')
@section('title', __('Order').' '.$order->order_number)
@section('content')
@php
    $canReceipt = in_array($order->status,['paid','preparing','packed','shipped','delivered']) || in_array($order->payment_status,['approved','paid']);
    $address = json_decode($order->shipping_address_snapshot ?? '{}', true) ?: [];
    $steps = ['pending_payment','paid','preparing','packed','shipped','delivered'];
    $currentStep = array_search($order->status, $steps, true);
@endphp
<div class="container">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4 page-heading">
        <div><div class="section-kicker">{{__('Order details')}}</div><h1 class="fw-bold mb-1 mt-2">{{$order->order_number}}</h1><div class="text-muted">{{$order->created_at?->format('d/m/Y H:i')}}</div></div>
        <div class="d-flex gap-2 flex-wrap"><a class="btn btn-outline-brand rounded-pill" href="{{route('member.orders')}}">{{__('Back to Orders')}}</a>@if($canReceipt)<a class="btn btn-brand rounded-pill" href="{{route('receipts.show',$order)}}"><i class="bi bi-receipt"></i> {{__('Receipt')}}</a>@endif</div>
    </div>

    <div class="bg-white rounded-4 shadow-sm p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4"><div><h3 class="fw-bold mb-1">{{__('Order status')}}</h3><span class="badge rounded-pill {{$order->payment_status_badge_class}}">{{$order->payment_status_label}}</span></div><span class="badge rounded-pill fs-6 {{$order->status_badge_class}}">{{$order->status_label}}</span></div>
        @if($order->status === 'cancelled')
            <div class="alert alert-secondary mb-0">{{__('This order has been cancelled.')}}</div>
        @else
            <div class="order-timeline">@foreach($steps as $index=>$step)<div class="order-step {{is_int($currentStep) && $index <= $currentStep ? 'done' : ''}}"><span><i class="bi {{$index < $currentStep ? 'bi-check-lg' : 'bi-circle-fill'}}"></i></span><small>{{\App\Models\Order::statusLabels()[$step] ?? $step}}</small></div>@endforeach</div>
        @endif
        @if($order->shipment?->tracking_number)<div class="mt-4 p-3 rounded-4 bg-light"><span class="text-muted">{{__('Tracking number')}}</span><div class="fw-bold fs-5">{{$order->shipment->tracking_number}}</div>@if($order->shipment->carrier)<span>{{$order->shipment->carrier}}</span>@endif</div>@endif
    </div>

    <div class="row g-4">
        <div class="col-lg-8"><div class="bg-white rounded-4 shadow-sm p-4 h-100"><h3 class="fw-bold mb-3">{{__('Products')}}</h3><div class="table-responsive"><table class="table align-middle"><thead><tr><th>{{__('Products')}}</th><th>SKU</th><th class="text-center">{{__('Quantity')}}</th><th class="text-end">{{__('Total')}}</th></tr></thead><tbody>@foreach($order->items as $i)<tr><td class="fw-bold">{{$i->product?->display_name ?? $i->product_name}}</td><td class="text-muted">{{$i->product->sku ?? '-'}}</td><td class="text-center">{{$i->quantity}}</td><td class="text-end fw-bold">฿{{number_format($i->total,2)}}</td></tr>@endforeach</tbody></table></div></div></div>
        <div class="col-lg-4">
            <div class="bg-white rounded-4 shadow-sm p-4 mb-4"><h3 class="fw-bold fs-5 mb-3">{{__('Payment summary')}}</h3><div class="d-flex justify-content-between mb-2"><span>{{__('Subtotal')}}</span><span>฿{{number_format($order->subtotal,2)}}</span></div><div class="d-flex justify-content-between mb-2"><span>VAT</span><span>฿{{number_format($order->vat_amount,2)}}</span></div><div class="d-flex justify-content-between mb-2"><span>{{__('Shipping Fee')}}</span><span>฿{{number_format($order->shipping_fee,2)}}</span></div><hr><div class="d-flex justify-content-between fs-5"><strong>{{__('Grand Total')}}</strong><strong class="text-danger">฿{{number_format($order->total,2)}}</strong></div><div class="small text-muted mt-2">{{$order->shipping_rule_note}}</div><div class="mt-3"><span class="text-muted">{{__('Payment Method')}}:</span> <strong>{{strtoupper($order->payment?->method ?? '-')}}</strong></div></div>
            <div class="bg-white rounded-4 shadow-sm p-4"><h3 class="fw-bold fs-5 mb-3">{{__('Shipping address')}}</h3><div class="fw-bold">{{$address['recipient_name'] ?? '-'}}</div><div>{{$address['phone'] ?? '-'}}</div><div class="text-muted mt-2">{{collect([$address['address'] ?? null,$address['subdistrict'] ?? null,$address['district'] ?? null,$address['province'] ?? null,$address['postal_code'] ?? null])->filter()->implode(' ')}}</div></div>
        </div>
    </div>
</div>
<style>
.order-timeline{display:grid;grid-template-columns:repeat(6,1fr);gap:.5rem;position:relative}.order-timeline:before{content:'';position:absolute;left:8%;right:8%;top:18px;height:3px;background:#e9ecef}.order-step{position:relative;text-align:center;color:#9aa2a0}.order-step span{position:relative;z-index:1;width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto .55rem;background:#edf0ef;color:#9aa2a0}.order-step.done{color:var(--brand);font-weight:700}.order-step.done span{background:var(--brand);color:#fff;box-shadow:0 7px 18px color-mix(in srgb,var(--brand) 25%,transparent)}.order-step small{display:block;font-size:.72rem}@media(max-width:767px){.order-timeline{grid-template-columns:repeat(3,1fr);row-gap:1rem}.order-timeline:before{display:none}}
</style>
@endsection
