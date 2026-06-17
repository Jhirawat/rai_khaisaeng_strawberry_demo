@extends('layouts.app')
@section('content')
<div class="container">
    <section class="hero-banner mb-5 p-4 p-lg-5">
        <div class="row align-items-center w-100 g-4">
            <div class="col-lg-7">
                <span class="badge bg-light text-danger rounded-pill px-3 py-2 mb-3">{{__('Community products from Samoeng')}}</span>
                <h1>{{__('Fresh strawberries and quality processed products')}}</h1>
                <p class="lead mt-3">{{__('Direct from Rai Khaisaeng Strawberry Farm, Chiang Mai')}}</p>
                <a class="btn btn-light btn-lg rounded-pill px-4 me-2" href="{{route('shop.products')}}">{{__('Shop Products')}}</a>
                <a class="btn btn-outline-light btn-lg rounded-pill px-4" href="#featured">{{__('View Featured Products')}}</a>
            </div>
            <div class="col-lg-5">
                <div class="hero-card">
                    <h5 class="fw-bold text-danger">{{__('Popular Categories')}}</h5>
                    <div class="row g-2 mt-2">
                        @foreach($categories->take(6) as $c)
                            <div class="col-6"><a class="btn btn-outline-brand w-100 rounded-pill" href="{{route('shop.products',['category'=>$c->slug])}}">{{$c->display_name}}</a></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="promo-strip mb-5">
        <div class="row g-0 align-items-center">
            <div class="col-lg-6 promo-img" @if(!empty($homePromotion?->image_url)) style="background-image:url('{{$homePromotion->image_url}}')" @endif></div>
            <div class="col-lg-6 promo-content">
                <span class="text-danger fw-bold">{{$homePromotion->subtitle ?? __('Promotion / Advertising Area')}}</span>
                <h2 class="fw-bold mt-2">{{$homePromotion->title ?? __('Souvenirs from Rai Khaisaeng Strawberry Farm')}}</h2>
                <p class="text-muted">{{$homePromotion->description ?? __('This long banner can be changed to seasonal promotions, new products, or community campaigns.')}}</p>
                <a href="{{$homePromotion->button_url ?? route('shop.products')}}" class="btn btn-brand rounded-pill px-4">{{$homePromotion->button_text ?? __('View Promotions')}}</a>
            </div>
        </div>
    </section>

    <div id="featured" class="d-flex align-items-center justify-content-between mb-3">
        <h3 class="fw-bold mb-0">{{__('Featured Products')}}</h3>
        <a href="{{route('shop.products')}}" class="btn btn-outline-brand rounded-pill">{{__('View All')}}</a>
    </div>
    <div class="row g-4">
        @foreach($featured as $p)
                        <div class="col-md-6 col-lg-3">
                <div class="card product-card h-100">
                    <div class="product-thumb">
                        <img src="{{$p->primary_image_url}}" alt="{{$p->display_name}}">
                    </div>
                    <div class="card-body">
                        <h5 class="fw-bold">{{$p->display_name}}</h5>
                        <p class="text-muted mb-2">{{$p->category->display_name}}</p>
                        <div class="fs-5 fw-bold text-danger mb-3">฿{{number_format($p->price,2)}}</div>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{route('shop.product',$p)}}" class="btn btn-sm btn-outline-brand rounded-pill">{{__('View Details')}}</a>
                            @auth
                                <form class="js-add-to-cart" method="post" action="{{route('member.cart.add',$p)}}">@csrf<input type="hidden" name="quantity" value="1"><button class="btn btn-sm btn-brand rounded-pill"><i class="bi bi-cart-plus"></i> {{__('Add to Cart')}}</button></form>
                            @else
                                <a href="{{route('login')}}" class="btn btn-sm btn-brand rounded-pill"><i class="bi bi-lock"></i> {{__('Add to Cart')}}</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
