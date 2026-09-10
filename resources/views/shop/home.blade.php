@extends('layouts.app')
@section('content')
@php
    $homeHeroImage = \App\Models\Setting::getValue(
        'home_hero_image',
        'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?auto=format&fit=crop&w=1600&q=85'
    );
@endphp
<div class="container storefront-home">
    <section class="farm-hero mb-5">
        <div class="farm-hero-copy">
            <div class="hero-kicker"><span></span>{{__('Community products from Samoeng')}}</div>
            <h1>{{__('Fresh strawberries and quality processed products')}}</h1>
            <p class="hero-lead">{{__('Direct from Rai Khaisaeng Strawberry Farm, Chiang Mai')}}</p>
            <div class="hero-actions">
                <a class="btn btn-brand btn-lg rounded-pill px-4" href="{{route('shop.products')}}">{{__('Shop Products')}} <i class="bi bi-arrow-up-right"></i></a>
                <a class="hero-text-link" href="#featured">{{__('View Featured Products')}} <i class="bi bi-arrow-down"></i></a>
            </div>
            <div class="hero-proof" aria-label="Farm highlights">
                <div><strong>20+</strong><span>{{__('Years of farming experience')}}</span></div>
                <div><strong>72</strong><span>{{__('Farm and community products')}}</span></div>
                <div><strong>100%</strong><span>{{__('From local growers')}}</span></div>
            </div>
        </div>
        <div class="farm-hero-media" style="background-image:url('{{ str_starts_with($homeHeroImage, 'http') ? $homeHeroImage : asset($homeHeroImage) }}')">
            <div class="hero-season-card">
                <span class="season-dot"></span>
                <div><small>{{__('Now in season')}}</small><strong>{{__('Fresh Samoeng strawberries')}}</strong></div>
            </div>
            <div class="hero-categories">
                <span>{{__('Explore by category')}}</span>
                <div>
                    @foreach($categories->take(4) as $c)
                        <a href="{{route('shop.products',['category'=>$c->slug])}}">{{$c->display_name}} <i class="bi bi-arrow-up-right"></i></a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="farm-values mb-5" aria-label="Why shop with us">
        <article><i class="bi bi-flower1"></i><div><strong>{{__('Picked with care')}}</strong><span>{{__('Selected by growers who know every harvest')}}</span></div></article>
        <article><i class="bi bi-box-seam"></i><div><strong>{{__('Packed close to the farm')}}</strong><span>{{__('Freshness protected from our farm to your home')}}</span></div></article>
        <article><i class="bi bi-people"></i><div><strong>{{__('Community made')}}</strong><span>{{__('Every order supports local families in Samoeng')}}</span></div></article>
    </section>

    <section class="season-story mb-5">
        <div class="promo-img" @if(!empty($homePromotion?->image_url)) style="background-image:url('{{$homePromotion->image_url}}')" @endif></div>
        <div class="season-story-copy">
            <span class="section-kicker">{{$homePromotion->subtitle ?? __('Promotion / Advertising Area')}}</span>
            <h2>{{$homePromotion->title ?? __('Souvenirs from Rai Khaisaeng Strawberry Farm')}}</h2>
            <p>{{$homePromotion->description ?? __('This long banner can be changed to seasonal promotions, new products, or community campaigns.')}}</p>
            <a href="{{$homePromotion->button_url ?? route('shop.products')}}" class="btn btn-outline-brand rounded-pill px-4">{{$homePromotion->button_text ?? __('View Promotions')}} <i class="bi bi-arrow-right"></i></a>
        </div>
    </section>

    <section id="featured" class="featured-section">
        <div class="section-title-row">
            <div><span class="section-kicker">{{__('Selected from this harvest')}}</span><h2>{{__('Featured Products')}}</h2></div>
            <a href="{{route('shop.products')}}" class="view-all-link">{{__('View All')}} <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            @forelse($featured as $p)
                @php $stock = $p->inventory->quantity ?? 0; @endphp
                <div class="col-6 col-lg-3">
                    <article class="card product-card h-100">
                        <a class="product-thumb" href="{{route('shop.product',$p)}}">
                            <img src="{{$p->primary_image_url}}" alt="{{$p->display_name}}" loading="lazy">
                            @if($stock > 0 && $stock <= 10)<span class="stock-chip">{{__('Only')}} {{$stock}} {{__('left')}}</span>@endif
                        </a>
                        <div class="card-body">
                            <a class="product-category" href="{{route('shop.products',['category'=>$p->category->slug])}}">{{$p->category->display_name}}</a>
                            <h3><a href="{{route('shop.product',$p)}}">{{$p->display_name}}</a></h3>
                            <div class="product-card-bottom">
                                <strong class="product-price">฿{{number_format($p->price,2)}}</strong>
                                @auth
                                    <form class="js-add-to-cart" method="post" action="{{route('member.cart.add',$p)}}">@csrf<input type="hidden" name="quantity" value="1"><button class="quick-add" aria-label="{{__('Add to Cart')}} {{$p->display_name}}" @disabled($stock < 1)><i class="bi bi-plus-lg"></i></button></form>
                                @else
                                    <a href="{{route('login')}}" class="quick-add" aria-label="{{__('Login to Buy')}} {{$p->display_name}}"><i class="bi bi-plus-lg"></i></a>
                                @endauth
                            </div>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12"><div class="empty-harvest"><i class="bi bi-basket"></i><p>{{__('Featured products are being prepared.')}}</p><a href="{{route('shop.products')}}" class="btn btn-brand rounded-pill">{{__('View All')}}</a></div></div>
            @endforelse
        </div>
    </section>
</div>

<style>
.storefront-home{padding-top:1.15rem}.farm-hero{display:grid;grid-template-columns:minmax(0,.9fr) minmax(0,1.1fr);min-height:650px;background:#f4efe6;border:1px solid rgba(38,62,45,.12);border-radius:32px;overflow:hidden}.farm-hero-copy{padding:clamp(2.5rem,5vw,5.5rem);display:flex;flex-direction:column;justify-content:center}.hero-kicker,.section-kicker{display:flex;align-items:center;gap:.6rem;color:var(--brand);font-size:.76rem;font-weight:800;letter-spacing:.1em;text-transform:uppercase}.hero-kicker span{width:8px;height:8px;border-radius:50%;background:var(--brand);box-shadow:0 0 0 6px color-mix(in srgb,var(--brand) 12%,transparent)}.farm-hero h1{font-size:clamp(2.75rem,5.2vw,5.8rem);line-height:.97;letter-spacing:-.055em;margin:1.35rem 0 1.5rem;color:#183126;font-weight:800}.hero-lead{max-width:530px;color:#65726a;font-size:1.08rem;line-height:1.75}.hero-actions{display:flex;align-items:center;gap:1.5rem;margin:1.5rem 0 3.25rem}.hero-text-link{color:#243d31;text-decoration:none;font-weight:700;border-bottom:1px solid #9aa69d;padding:.6rem 0}.hero-proof{display:grid;grid-template-columns:repeat(3,1fr);gap:1.25rem;padding-top:1.5rem;border-top:1px solid rgba(38,62,45,.13)}.hero-proof div{display:grid;gap:.25rem}.hero-proof strong{color:#183126;font-size:1.5rem}.hero-proof span{color:#78847d;font-size:.72rem;line-height:1.35}.farm-hero-media{min-height:520px;background-size:cover;background-position:center;position:relative}.farm-hero-media:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(8,30,19,.03),rgba(8,30,19,.5))}.hero-season-card{position:absolute;z-index:2;top:1.5rem;right:1.5rem;background:rgba(255,255,255,.94);backdrop-filter:blur(12px);padding:.8rem 1rem;border-radius:14px;display:flex;gap:.7rem;align-items:center;box-shadow:0 12px 30px rgba(0,0,0,.12)}.season-dot{width:9px;height:9px;background:#5ba04c;border-radius:50%;box-shadow:0 0 0 5px rgba(91,160,76,.13)}.hero-season-card div{display:grid}.hero-season-card small{color:#7a857e;font-size:.65rem;text-transform:uppercase;letter-spacing:.08em}.hero-season-card strong{color:#183126;font-size:.82rem}.hero-categories{position:absolute;z-index:2;left:1.75rem;right:1.75rem;bottom:1.75rem;background:rgba(12,36,24,.86);backdrop-filter:blur(14px);padding:1.2rem 1.35rem;border:1px solid rgba(255,255,255,.18);border-radius:18px;color:#fff}.hero-categories>span{display:block;font-size:.68rem;opacity:.68;text-transform:uppercase;letter-spacing:.11em;margin-bottom:.7rem}.hero-categories>div{display:flex;flex-wrap:wrap;gap:.5rem}.hero-categories a{color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.28);border-radius:999px;padding:.55rem .8rem;font-size:.76rem}.farm-values{display:grid;grid-template-columns:repeat(3,1fr);border-block:1px solid rgba(38,62,45,.12)}.farm-values article{display:flex;align-items:center;gap:1rem;padding:1.6rem 2rem;border-right:1px solid rgba(38,62,45,.12)}.farm-values article:last-child{border-right:0}.farm-values i{font-size:1.5rem;color:var(--brand)}.farm-values div{display:grid;gap:.15rem}.farm-values strong{color:#1c3328;font-size:.9rem}.farm-values span{color:#7b867f;font-size:.72rem}.season-story{display:grid;grid-template-columns:1.05fr .95fr;background:var(--shop-card);border-radius:28px;overflow:hidden;box-shadow:0 18px 60px rgba(31,54,40,.09)}.season-story .promo-img{min-height:390px;height:auto}.season-story-copy{padding:clamp(2.25rem,5vw,5rem);display:flex;flex-direction:column;align-items:flex-start;justify-content:center}.season-story h2,.featured-section h2{font-size:clamp(2rem,3.5vw,3.8rem);line-height:1.05;letter-spacing:-.045em;color:#193328;font-weight:800;margin:1rem 0}.season-story p{color:#6e7c74;line-height:1.75;max-width:570px}.section-title-row{display:flex;align-items:end;justify-content:space-between;margin-bottom:2rem}.featured-section{padding:3rem 0 5rem}.featured-section h2{margin:.55rem 0 0}.view-all-link{color:#213a2e;text-decoration:none;font-weight:750;border-bottom:1px solid #8e9b93;padding-bottom:.35rem}.product-card{border:0;border-radius:18px!important;background:transparent!important;box-shadow:none!important;overflow:visible!important}.product-card:hover{transform:none!important;box-shadow:none!important}.product-card .product-thumb{height:300px;border-radius:18px;background:#eee7dd;position:relative}.product-card .product-thumb img{transition:transform .45s ease}.product-card:hover .product-thumb img{transform:scale(1.035)}.stock-chip{position:absolute;top:.8rem;left:.8rem;background:#fff5f5;color:var(--brand);border-radius:999px;padding:.4rem .65rem;font-size:.65rem;font-weight:800}.product-card .card-body{padding:1rem .15rem 0!important}.product-category{color:#809087;text-transform:uppercase;letter-spacing:.08em;font-size:.65rem;font-weight:700;text-decoration:none}.product-card h3{font-size:1rem;line-height:1.45;min-height:auto!important;margin:.35rem 0 .75rem}.product-card h3 a{color:#21372d;text-decoration:none}.product-card-bottom{display:flex;align-items:center;justify-content:space-between}.product-price{font-size:1.08rem;color:var(--brand)}.quick-add{width:38px;height:38px;border-radius:50%;display:grid;place-items:center;border:0;background:#173c2a;color:#fff;text-decoration:none;transition:.2s}.quick-add:hover{background:var(--brand);color:#fff;transform:rotate(90deg)}.quick-add:disabled{opacity:.35}.empty-harvest{text-align:center;padding:4rem;background:#f4efe6;border-radius:24px}.empty-harvest i{font-size:2.4rem;color:var(--brand)}.empty-harvest p{margin:1rem 0}
@media(max-width:991.98px){.farm-hero{grid-template-columns:1fr}.farm-hero-copy{padding:3rem}.farm-hero-media{min-height:520px}.farm-values{grid-template-columns:1fr}.farm-values article{border-right:0;border-bottom:1px solid rgba(38,62,45,.12)}.season-story{grid-template-columns:1fr}.season-story .promo-img{min-height:300px}.product-card .product-thumb{height:240px}}
@media(max-width:575.98px){.storefront-home{padding-top:.5rem}.farm-hero{border-radius:22px;min-height:0}.farm-hero-copy{padding:2rem 1.25rem}.farm-hero h1{font-size:2.8rem}.hero-actions{flex-direction:column;align-items:flex-start;margin-bottom:2.5rem}.hero-proof{gap:.6rem}.hero-proof strong{font-size:1.1rem}.hero-proof span{font-size:.62rem}.farm-hero-media{min-height:420px}.hero-season-card{top:1rem;right:1rem}.hero-categories{left:1rem;right:1rem;bottom:1rem}.farm-values article{padding:1.25rem}.season-story{border-radius:20px}.season-story .promo-img{min-height:220px}.season-story-copy{padding:2rem 1.25rem}.section-title-row{align-items:flex-start;gap:1rem}.view-all-link{font-size:.8rem;white-space:nowrap}.product-card .product-thumb{height:180px!important}.product-card h3{font-size:.88rem}.product-price{font-size:.95rem}.quick-add{width:34px;height:34px}}
</style>
@endsection
