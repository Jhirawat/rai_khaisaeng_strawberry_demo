@extends('layouts.app')
@section('content')
<div class="container">
    @php $activeCategory = $categories->firstWhere('slug', request('category')); @endphp
    <div class="category-banner mb-4">
        <div class="small mb-2"><a href="{{route('shop.home')}}">{{__('Home')}}</a> / <a href="{{route('shop.products')}}">{{__('Products')}}</a> @if($activeCategory) / {{$activeCategory->display_name}} @endif</div>
        <h2 class="fw-bold mb-1">{{ $activeCategory?->display_name ?? __('All Products') }}</h2>
        <p class="mb-0">{{__('Choose products from Rai Khaisaeng Strawberry Farm. Search, filter, and add products to cart quickly.')}}</p>
    </div>
    <form id="productFilter" class="row g-3 mb-4 p-3 bg-white rounded-4 shadow-sm" method="get" action="{{route('shop.products')}}">
        <div class="col-lg-5"><input name="search" class="form-control form-control-lg rounded-pill" placeholder="{{__('Search products')}}" value="{{request('search')}}"></div>
        <div class="col-lg-4"><select id="categorySelect" name="category" class="form-select form-select-lg rounded-pill"><option value="">{{__('All Products')}}</option>@foreach($categories as $c)<option value="{{$c->slug}}" @selected(request('category')==$c->slug)>{{$c->display_name}}</option>@endforeach</select></div>
        <div class="col-lg-3"><button class="btn btn-brand btn-lg rounded-pill w-100">{{__('Filter Products')}}</button></div>
    </form>
    <div class="row g-4">
        <aside class="col-lg-3">
            <div class="bg-white rounded-4 shadow-sm p-3 sticky-lg-top" style="top:90px">
                <div class="fw-bold mb-3">{{__('Product Categories')}}</div>
                <a class="category-filter-link {{request('category')?'':'active'}}" href="{{route('shop.products')}}">{{__('All')}}</a>
                @foreach($categories as $c)<a class="category-filter-link {{request('category')===$c->slug?'active':''}}" href="{{route('shop.products',['category'=>$c->slug])}}">{{$c->display_name}}</a>@endforeach
                <hr><div class="fw-bold mb-2">{{__('Filters')}}</div><div class="text-muted small">{{__('Search by product name and choose a category above or use the sidebar category list.')}}</div>
            </div>
        </aside>
        <section class="col-lg-9">
            <div class="row g-4">
                @foreach($products as $p)
                    <div class="col-md-6 col-xl-4">
                        <div class="card product-card h-100">
                            <div class="product-thumb"><img src="{{$p->primary_image_url}}" alt="{{$p->display_name}}"></div>
                            <div class="card-body">
                                <h5 class="fw-bold">{{$p->display_name}}</h5>
                                <p class="text-muted mb-2"><a class="text-muted text-decoration-none" href="{{route('shop.products',['category'=>$p->category->slug])}}">{{$p->category->display_name}}</a></p>
                                <div class="fs-5 fw-bold text-danger mb-3">฿{{number_format($p->price,2)}}</div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-sm btn-outline-brand rounded-pill" href="{{route('shop.product',$p)}}">{{__('Details')}}</a>
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
            @if($products->hasPages())
                <div class="mt-4 d-flex justify-content-center gap-2 product-pagination">
                    @if($products->onFirstPage())<span class="page-btn disabled">{{__('Previous')}}</span>@else<a class="page-btn" href="{{$products->previousPageUrl()}}">{{__('Previous')}}</a>@endif
                    @for($page=1; $page <= $products->lastPage(); $page++) @if($page == $products->currentPage())<span class="page-btn active">{{$page}}</span>@else<a class="page-btn" href="{{$products->url($page)}}">{{$page}}</a>@endif @endfor
                    @if($products->hasMorePages())<a class="page-btn" href="{{$products->nextPageUrl()}}">{{__('Next')}}</a>@else<span class="page-btn disabled">{{__('Next')}}</span>@endif
                </div>
            @endif
        </section>
    </div>
</div>
<style>
.category-banner{background:linear-gradient(135deg,#ffe9ed,#fff7f8);border-radius:28px;padding:34px;box-shadow:0 14px 35px rgba(220,47,67,.08)}.category-banner a{color:#dc2f43;text-decoration:none;font-weight:700}.category-filter-link{display:block;padding:10px 14px;margin-bottom:8px;border-radius:14px;text-decoration:none;color:#333;font-weight:700}.category-filter-link.active,.category-filter-link:hover{background:#dc2f43;color:#fff}.product-pagination .page-btn{display:inline-flex;align-items:center;justify-content:center;min-width:44px;height:42px;padding:0 14px;border:1px solid #ffd1d8;border-radius:999px;text-decoration:none;color:#dc2f43;background:#fff;font-weight:700}.product-pagination .page-btn.active,.product-pagination .page-btn:hover{background:#dc2f43;color:#fff;border-color:#dc2f43}.product-pagination .page-btn.disabled{color:#adb5bd;background:#f8f9fa;border-color:#e9ecef}
</style>
@endsection
@push('scripts')
<script>document.getElementById('categorySelect')?.addEventListener('change',()=>document.getElementById('productFilter').submit());</script>
@endpush
