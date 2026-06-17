@extends('layouts.app')
@section('content')
<div class="container">
    <div class="row g-4 align-items-start">
        <div class="col-lg-6">
            @php $mainImage=$product->images->first(); @endphp
            <div class="product-card p-2">
                <div class="product-thumb" style="height:430px;border-radius:16px">
                    @if($mainImage)<img src="{{$mainImage->url}}" alt="{{$product->display_name}}">@else<i class="bi bi-image"></i>@endif
                </div>
            </div>
            @if($product->images->count()>1)
                <div class="row g-2 mt-2">@foreach($product->images as $img)<div class="col-3"><img class="img-fluid rounded-3 border" src="{{$img->url}}" alt="{{$product->display_name}}"></div>@endforeach</div>
            @endif
        </div>
        <div class="col-lg-6">
            <div class="bg-white rounded-4 shadow-sm p-4">
                <span class="badge bg-danger-subtle text-danger rounded-pill px-3 py-2 mb-3">{{$product->category->display_name}}</span>
                <h2 class="fw-bold">{{$product->display_name}}</h2>
                <h3 class="text-danger fw-bold my-3">฿{{number_format($product->price,2)}}</h3>
                <p class="text-muted">{{$product->display_description}}</p>
                <p>{{__('In Stock')}}: <b>{{$product->inventory->quantity ?? 0}}</b> {{__('pieces')}}</p>
                @auth
                    <form method="post" action="{{route('member.cart.add',$product)}}" class="js-add-to-cart d-flex flex-wrap gap-2 align-items-center product-buy-form">@csrf
                        <div class="qty-pill product-qty-control" data-min="1" data-max="{{$product->inventory->quantity ?? 999}}">
                            <button type="button" class="btn btn-outline-brand js-qty-minus" aria-label="ลดจำนวน">−</button>
                            <input type="number" name="quantity" value="1" min="1" max="{{$product->inventory->quantity ?? 999}}" class="qty-num js-qty-input" aria-label="จำนวนสินค้า">
                            <button type="button" class="btn btn-brand js-qty-plus" aria-label="เพิ่มจำนวน">+</button>
                        </div>
                        <button class="btn btn-brand rounded-pill px-4"><i class="bi bi-cart-plus"></i> {{__('Add to Cart')}}</button>
                    </form>
                    <script>
                    document.addEventListener('click', function(e){
                        const btn = e.target.closest('.js-qty-minus,.js-qty-plus');
                        if(!btn) return;
                        const wrap = btn.closest('.product-qty-control');
                        const input = wrap.querySelector('.js-qty-input');
                        const min = parseInt(input.min || wrap.dataset.min || '1', 10);
                        const max = parseInt(input.max || wrap.dataset.max || '999', 10);
                        let val = parseInt(input.value || '1', 10);
                        val += btn.classList.contains('js-qty-plus') ? 1 : -1;
                        input.value = Math.max(min, Math.min(max, val));
                    });
                    </script>
                @else
                    <a href="{{route('login')}}" class="btn btn-brand rounded-pill px-4"><i class="bi bi-lock"></i> {{__('Login to Buy')}}</a>
                @endauth
            </div>
        </div>
    </div>
</div>
@endsection
