@extends('layouts.app')
@section('content')
<div class="container">
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="bg-white rounded-4 shadow-sm p-4 sticky-top" style="top:100px">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div style="width:54px;height:54px;border-radius:50%;background:#ffe3e6;color:var(--brand);display:flex;align-items:center;justify-content:center;font-size:1.8rem"><i class="bi bi-person-fill"></i></div>
                    <div><div class="fw-bold">{{auth()->user()->name}}</div><div class="small text-muted">{{auth()->user()->email}}</div></div>
                </div>
                <a class="side-link" href="{{route('member.profile')}}"><i class="bi bi-person"></i> {{__('My Account')}}</a>
                <a class="side-link active" href="{{route('member.orders')}}"><i class="bi bi-bag-check"></i> {{__('My Purchases')}}</a>
                <a class="side-link" href="{{route('member.cart')}}"><i class="bi bi-cart3"></i> {{__('Shopping Cart')}}</a>
            </div>
        </div>
        <div class="col-lg-9">
            <div class="bg-white rounded-4 shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="fw-bold mb-1">{{__('Order History')}}</h2>
                        <p class="text-muted mb-0">{{__('Check product status, payment status, and order details')}}</p>
                    </div>
                    <a class="btn btn-outline-brand rounded-pill" href="{{route('shop.products')}}">{{__('Shop Products')}}</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle order-table">
                        <thead class="table-light">
                            <tr>
                                <th>{{__('Order Number')}}</th>
                                <th class="text-end">{{__('Grand Total')}}</th>
                                <th class="text-center">{{__('Order Status')}}</th>
                                <th class="text-center">{{__('Payment Status')}}</th>
                                <th class="text-end">{{__('Details')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($orders as $o)
                            <tr>
                                <td>
                                    <a class="fw-bold" href="{{route('member.orders.show',$o)}}">{{$o->order_number}}</a>
                                    <div class="small text-muted">{{$o->created_at?->format('d/m/Y H:i')}}</div>
                                </td>
                                <td class="text-end fw-bold text-danger">฿{{number_format($o->total,2)}}</td>
                                <td class="text-center"><span class="badge rounded-pill {{$o->status_badge_class}}">{{$o->status_label}}</span></td>
                                <td class="text-center"><span class="badge rounded-pill {{$o->payment_status_badge_class}}">{{$o->payment_status_label}}</span></td>
                                <td class="text-end"><a class="btn btn-sm btn-brand rounded-pill" href="{{route('member.orders.show',$o)}}">{{__('View Details')}}</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-5 text-muted">{{__('No orders yet')}}</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<style>.side-link{display:block;padding:.85rem 1rem;border-radius:14px;text-decoration:none;color:#333;font-weight:700;margin:.2rem 0}.side-link i{color:var(--brand);margin-right:.5rem}.side-link:hover,.side-link.active{background:#fff3f4;color:var(--brand)}.order-table td,.order-table th{padding:1rem}</style>
@endsection
