@extends('layouts.app')
@section('content')
<div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 page-heading">
        <div><h2 class="fw-bold mb-1">{{__('Shopping Cart')}}</h2><p class="text-muted mb-0">{{__('Check products and adjust quantities before payment')}}</p></div>
        <a class="btn btn-outline-brand rounded-pill" href="{{route('shop.products')}}"><i class="bi bi-arrow-left"></i> {{__('Continue Shopping')}}</a>
    </div>
    @php $grandTotal = $cart->items->sum(fn($i)=>$i->price*$i->quantity); @endphp
    @if($cart->items->isEmpty())
        <div class="bg-white rounded-4 shadow-sm p-5 text-center"><i class="bi bi-cart-x display-3 text-muted"></i><h4 class="mt-3">{{__('Your cart is empty')}}</h4><a class="btn btn-brand rounded-pill mt-3" href="{{route('shop.products')}}">{{__('Shop Now')}}</a></div>
    @else
    <div class="row g-4">
        <div class="col-lg-9">
            <div class="table-responsive bg-white rounded-4 shadow-sm">
                <table class="table align-middle mb-0 cart-table">
                    <thead class="table-light"><tr><th>{{__('Products')}}</th><th class="text-end">{{__('Unit Price')}}</th><th class="text-center">{{__('Quantity')}}</th><th class="text-end">{{__('Total')}}</th><th class="text-end">{{__('Action')}}</th></tr></thead>
                    <tbody>
                    @foreach($cart->items as $i)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="{{$i->product->primary_image_url}}" alt="{{$i->product->display_name}}" class="cart-product-img" onerror="this.src='{{asset('images/products/placeholder.svg')}}'">
                                    <div class="fw-bold">{{$i->product->display_name}}</div>
                                </div>
                            </td>
                            <td class="text-end">฿{{number_format($i->price,2)}}</td>
                            <td class="text-center">
                                <div class="qty-pill">
                                    <form method="post" action="{{route('member.cart.update',$i)}}">@csrf @method('patch')<input type="hidden" name="quantity" value="{{max(1,$i->quantity-1)}}"><button type="submit" class="btn btn-outline-brand" @disabled($i->quantity<=1)>−</button></form>
                                    <span class="qty-num">{{$i->quantity}}</span>
                                    <form method="post" action="{{route('member.cart.update',$i)}}">@csrf @method('patch')<input type="hidden" name="quantity" value="{{$i->quantity+1}}"><button type="submit" class="btn btn-brand">+</button></form>
                                </div>
                            </td>
                            <td class="text-end text-danger fw-bold">฿{{number_format($i->price*$i->quantity,2)}}</td>
                            <td class="text-end cart-actions"><form method="post" action="{{route('member.cart.destroy',$i)}}">@csrf @method('delete')<button class="btn btn-sm btn-outline-danger rounded-pill"><i class="bi bi-trash"></i> {{__('Remove')}}</button></form></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="bg-white rounded-4 shadow-sm p-4 sticky-top" style="top:100px">
                <h5 class="fw-bold">{{__('Order Summary')}}</h5>
                <div class="d-flex justify-content-between mt-3"><span>{{__('Subtotal')}}</span><b>฿{{number_format($grandTotal,2)}}</b></div>
                <div class="d-flex justify-content-between text-muted mt-2"><span>{{__('Shipping Fee')}}</span><span>{{__('Calculated at Checkout')}}</span></div>
                <hr><a class="btn btn-brand btn-lg rounded-pill w-100" href="{{route('member.checkout')}}">Checkout <i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
    </div>
    @endif
</div>
<style>
.cart-product-img{width:72px;height:72px;object-fit:cover;border-radius:16px;background:#fff1f3;border:1px solid #f3c9cf}
@media(max-width:768px){.cart-product-img{width:56px;height:56px}}
</style>
@endsection
