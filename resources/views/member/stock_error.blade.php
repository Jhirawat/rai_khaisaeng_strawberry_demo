@extends('layouts.app')
@section('content')
<div class="container py-5">
    <div class="mx-auto bg-white rounded-4 shadow-sm p-5 text-center" style="max-width:720px">
        <div class="mb-3" style="width:86px;height:86px;border-radius:50%;background:#fff1f3;color:var(--brand);display:inline-flex;align-items:center;justify-content:center;font-size:2.8rem">
            <i class="bi bi-exclamation-triangle"></i>
        </div>
        <h2 class="fw-bold mb-2">{{__('Sorry, this product is out of stock')}}</h2>
        <p class="text-muted mb-4">
            {{__('The selected product does not have enough stock. Please adjust the quantity or choose another product.')}}
        </p>
        <div class="alert alert-warning rounded-4 text-start mx-auto" style="max-width:520px">
            <div class="fw-bold">{{$product?->display_name}}</div>
            <div>{{__('Requested Quantity')}}: <b>{{$requested}}</b></div>
            <div>{{__('Available Stock')}}: <b>{{$available}}</b></div>
        </div>
        <div class="d-flex gap-2 justify-content-center flex-wrap mt-4">
            <a href="{{route('member.cart')}}" class="btn btn-brand rounded-pill px-4">{{__('Back to Cart')}}</a>
            <a href="{{route('shop.products')}}" class="btn btn-outline-brand rounded-pill px-4">{{__('Choose Products Again')}}</a>
        </div>
    </div>
</div>
@endsection
