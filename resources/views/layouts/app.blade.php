@php
    $shopThemeDefaults = [
        'shop_brand'=>'#C35B53','shop_brand_dark'=>'#9E4244','shop_soft'=>'#E8A7A1',
        'shop_background'=>'#FDFBF7','shop_card'=>'#FFFFFF','shop_text'=>'#4A3E3D',
        'shop_footer'=>'#1F4F38','shop_footer_dark'=>'#183F2D','shop_cream'=>'#E8A7A1','shop_nav_text'=>'#4A3E3D'
    ];
    $shopTheme = $shopThemeDefaults;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            foreach ($shopThemeDefaults as $k => $v) {
                $shopTheme[$k] = optional(\App\Models\Setting::where('key',$k)->first())->value ?: $v;
            }
        }
    } catch (\Throwable $e) { $shopTheme = $shopThemeDefaults; }
    $faviconPath = 'favicon.png';
    $logoThPath = 'images/logo-nav-th.png';
    $logoEnPath = 'images/logo-nav-en.png';
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            $faviconPath = optional(\App\Models\Setting::where('key','site_favicon')->first())->value ?: 'favicon.png';
            $logoThPath = optional(\App\Models\Setting::where('key','site_logo_th')->first())->value ?: 'images/logo-nav-th.png';
            $logoEnPath = optional(\App\Models\Setting::where('key','site_logo_en')->first())->value ?: 'images/logo-nav-en.png';
        }
    } catch (\Throwable $e) { $faviconPath = 'favicon.png'; $logoThPath = 'images/logo-nav-th.png'; $logoEnPath = 'images/logo-nav-en.png'; }
    $socialFacebook = '#'; $socialYoutube = '#'; $socialLine = '#';
    $homeHeroImage = 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?auto=format&fit=crop&w=1600&q=80';
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            $socialFacebook = optional(\App\Models\Setting::where('key','social_facebook_url')->first())->value ?: '#';
            $socialYoutube = optional(\App\Models\Setting::where('key','social_youtube_url')->first())->value ?: '#';
            $socialLine = optional(\App\Models\Setting::where('key','social_line_url')->first())->value ?: '#';
            $homeHeroImage = optional(\App\Models\Setting::where('key','home_hero_image')->first())->value ?: $homeHeroImage;
        }
    } catch (\Throwable $e) { $socialFacebook = '#'; $socialYoutube = '#'; $socialLine = '#';
    $homeHeroImage = 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?auto=format&fit=crop&w=1600&q=80'; }
@endphp
<!doctype html>
<html lang="{{app()->getLocale()}}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{__('Rai Khaisaeng Strawberry Farm')}} | Rai Khaisaeng Strawberry Shop</title>
    <link rel="icon" type="image/png" href="{{ asset($faviconPath) }}">
    <link rel="shortcut icon" href="{{ asset($faviconPath) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800;900&family=Kanit:wght@300;400;500;600;700;800;900&family=Bai+Jamjuree:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700;800;900&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/responsive-v24.css') }}">
    <style>
        :root{--brand:{{$shopTheme['shop_brand']}};--brand-dark:{{$shopTheme['shop_brand_dark']}};--soft:{{$shopTheme['shop_soft']}};--green:{{$shopTheme['shop_footer']}};--green-2:{{$shopTheme['shop_footer_dark']}};--cream:{{$shopTheme['shop_cream']}};--text:{{$shopTheme['shop_text']}};--nav-text:{{$shopTheme['shop_nav_text'] ?? '#13231F'}};--shop-bg:{{$shopTheme['shop_background']}};--shop-card:{{$shopTheme['shop_card']}};--muted:#6c757d}
        body{font-family:'Prompt','Inter','Kanit','Bai Jamjuree','Roboto','Segoe UI',Tahoma,sans-serif;background:var(--shop-bg);color:var(--text)}
        .navbar-main{background:var(--shop-card);box-shadow:0 2px 18px rgba(0,0,0,.08);position:sticky;top:0;z-index:1030}.brand-logo{font-weight:900;color:var(--nav-text);letter-spacing:.2px;text-decoration:none;font-size:1.25rem;display:flex;align-items:center;gap:.55rem}.brand-logo img{height:46px;width:auto;max-width:260px;object-fit:contain}.nav-link{font-weight:650;color:var(--nav-text)!important}.nav-link:hover{color:var(--brand)!important}
        .btn{line-height:1.45;display:inline-flex;align-items:center;justify-content:center;gap:.35rem}.btn-sm{line-height:1.45}.rounded-pill.btn,.btn.rounded-pill{min-height:2.45rem}.btn-sm.rounded-pill{min-height:2.15rem;padding-top:.38rem;padding-bottom:.38rem}.btn-brand{background:var(--brand);border-color:var(--brand);color:#fff}.register-nav-btn{padding-top:.62rem!important;padding-bottom:.62rem!important;line-height:1.35;display:inline-flex;align-items:center;margin-top:.15rem}.btn-brand:hover{background:var(--brand-dark);border-color:var(--brand-dark);color:#fff}.btn-outline-brand{border-color:var(--brand);color:var(--brand)}.btn-outline-brand:hover{background:var(--brand);color:#fff}.btn-green{background:var(--green);border-color:var(--green);color:#fff}.btn-green:hover{background:var(--green-2);border-color:var(--green-2);color:#fff}
        .avatar-dot{color:var(--brand);font-size:1.15rem}.cart-link{position:relative;font-size:1.35rem;color:var(--brand)!important;display:inline-flex;align-items:center;padding:.25rem .7rem!important}.cart-badge{position:absolute;top:-7px;right:-2px;background:var(--brand);color:#fff;border-radius:999px;font-size:.72rem;line-height:1;padding:.28rem .45rem;border:2px solid #fff;min-width:1.45rem;text-align:center}
        .hero-banner{background:linear-gradient(120deg,color-mix(in srgb,var(--brand) 88%,transparent),color-mix(in srgb,var(--brand) 52%,white)),url('{{ str_starts_with($homeHeroImage, 'http') ? $homeHeroImage : asset($homeHeroImage) }}');background-size:cover;background-position:center;border-radius:28px;color:#fff;min-height:360px;display:flex;align-items:center;box-shadow:0 16px 50px rgba(217,45,58,.25);overflow:hidden}.hero-banner h1{font-weight:900;font-size:clamp(2rem,4vw,4rem);line-height:1.05}.hero-card{background:color-mix(in srgb,var(--shop-card) 94%,transparent);color:#333;border-radius:18px;padding:1rem;box-shadow:0 10px 30px rgba(0,0,0,.12)}
        .promo-strip{background:linear-gradient(90deg,var(--shop-card),var(--soft));border:1px solid color-mix(in srgb,var(--brand) 22%,white);border-radius:22px;overflow:hidden}.promo-img{height:260px;background:url('https://images.unsplash.com/photo-1588165171080-c89acfa5ee83?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat}.promo-content{padding:2rem}
        .product-card{border:0;border-radius:18px;overflow:hidden;box-shadow:0 8px 28px rgba(0,0,0,.08);transition:.2s;background:var(--shop-card)}.product-card:hover{transform:translateY(-4px);box-shadow:0 12px 36px rgba(0,0,0,.12)}.product-thumb{height:180px;background:linear-gradient(135deg,var(--soft),var(--shop-card));display:flex;align-items:center;justify-content:center;overflow:hidden}.product-thumb img{width:100%;height:100%;object-fit:cover}.product-thumb i{font-size:3rem;color:#dc6b75}
        .footer-top{background:var(--cream);color:#4A3E3D;padding:.85rem 0;text-align:center;font-weight:800}.site-footer{background:var(--green);color:#EAF3EC;padding:3rem 0 1.5rem}.site-footer h4,.site-footer h5,.site-footer .fw-bold{color:#fff}.site-footer p,.site-footer li,.site-footer .footer-text{color:#EAF3EC}.site-footer a{color:#EAF3EC;text-decoration:none}.site-footer a:hover{color:var(--soft);text-decoration:underline}.site-footer hr{border-color:rgba(234,243,236,.32)}.social-circle{width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.95);display:inline-flex;align-items:center;justify-content:center;margin-right:.5rem;font-size:1.2rem;text-decoration:none;transition:transform .18s ease, box-shadow .18s ease}.social-circle:hover{transform:translateY(-3px);box-shadow:0 10px 22px rgba(0,0,0,.18);text-decoration:none!important}.site-footer a.social-facebook{color:#1877F2!important}.site-footer a.social-youtube{color:#FF0000!important}.site-footer a.social-line{color:#06C755!important}.footer-map{width:100%;height:150px;border:0;border-radius:16px;filter:saturate(.9)}.search-box{min-width:240px}.toast-cart{position:fixed;right:22px;bottom:22px;z-index:2000;display:none}.qty-pill{background:var(--soft);border:1px solid #f3c8cd;border-radius:999px;display:inline-flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 4px 12px rgba(217,45,58,.08)}.qty-pill form{margin:0;display:flex}.qty-pill .btn{border:0;border-radius:0;min-width:46px;height:42px;font-weight:900;display:flex;align-items:center;justify-content:center}.qty-pill .btn-outline-brand{background:var(--shop-card);border-right:1px solid #f3c8cd;color:var(--brand)}.qty-pill .btn-brand{background:var(--brand);color:#fff}.qty-pill .btn:disabled{background:#f5f5f5;color:#aaa}.qty-num{min-width:58px;height:42px;display:flex;align-items:center;justify-content:center;text-align:center;font-weight:900;color:var(--brand);background:var(--soft);border:0}.qty-num:focus{outline:0;box-shadow:inset 0 0 0 2px rgba(217,45,58,.18)}.product-qty-control .qty-num{width:58px;padding:0;-moz-appearance:textfield}.product-qty-control .qty-num::-webkit-inner-spin-button,.product-qty-control .qty-num::-webkit-outer-spin-button{appearance:none;margin:0}.pagination svg{width:1rem;height:1rem}.pagination .page-link{border-radius:10px;margin:0 .15rem;color:var(--brand)}.pagination .active .page-link{background:var(--brand);border-color:var(--brand);color:#fff}.cart-table th,.cart-table td{vertical-align:middle}.cart-actions{white-space:nowrap}.motion-fade{opacity:0;transform:translateY(18px);transition:opacity .55s ease,transform .55s ease}.motion-fade.is-visible{opacity:1;transform:translateY(0)}.motion-pop{transition:transform .18s ease,box-shadow .18s ease}.motion-pop:hover{transform:translateY(-3px)}@media (prefers-reduced-motion: reduce){*,*::before,*::after{scroll-behavior:auto!important;transition:none!important;animation:none!important}.motion-fade{opacity:1!important;transform:none!important}}@media(max-width:991px){.search-box{min-width:100%;margin:.5rem 0}.hero-banner{min-height:300px}.promo-img{height:180px}}
    </style>
</head>
<body>
@php
    $navCategories = \App\Models\Category::where('is_active',1)->orderBy('name')->get();
    $cartCount = 0;
    if(auth()->check()){
        $cart = \App\Models\Cart::where('user_id',auth()->id())->with('items')->first();
        $cartCount = $cart ? $cart->items->sum('quantity') : 0;
    }
    $currentLocale = app()->getLocale();
@endphp
<nav class="navbar navbar-expand-lg navbar-main py-3">
    <div class="container">
        <a class="brand-logo" href="{{route('shop.home')}}"><img src="{{ asset($currentLocale === 'en' ? $logoEnPath : $logoThPath) }}" alt="{{__('Rai Khaisaeng Strawberry Farm')}}"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-4 me-auto align-items-lg-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">{{__('Products')}}</a>
                    <ul class="dropdown-menu border-0 shadow">
                        <li><a class="dropdown-item" href="{{route('shop.products')}}">{{__('All Products')}}</a></li><li><hr class="dropdown-divider"></li>
                        @foreach($navCategories as $c)<li><a class="dropdown-item" href="{{route('shop.products',['category'=>$c->slug])}}">{{$c->display_name}}</a></li>@endforeach
                    </ul>
                </li>
                @auth<li class="nav-item"><a class="nav-link" href="{{route('member.orders')}}">{{__('Orders')}}</a></li>@endauth
                @auth @if(auth()->user()->role!='member')<li class="nav-item"><a class="nav-link" href="{{route('admin.dashboard')}}">Admin</a></li>@endif @endauth
            </ul>
            <form class="d-flex search-box me-lg-3" method="get" action="{{route('shop.products')}}"><input class="form-control rounded-pill" name="search" placeholder="{{__('Search products')}}" value="{{request('search')}}"></form>
            <div class="dropdown me-lg-3">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle rounded-pill" data-bs-toggle="dropdown"><i class="bi bi-globe2"></i> {{strtoupper($currentLocale)}}</button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item @if($currentLocale==='th') active @endif" href="{{route('language.switch','th')}}">{{__('Thai')}}</a></li>
                    <li><a class="dropdown-item @if($currentLocale==='en') active @endif" href="{{route('language.switch','en')}}">{{__('English')}}</a></li>
                </ul>
            </div>
            <div class="navbar-nav align-items-lg-center">
                @auth
                    <a class="nav-link cart-link me-lg-2" href="{{route('member.cart')}}"><i class="bi bi-cart3"></i><span id="cartBadge" class="cart-badge" @if($cartCount<1) style="display:none" @endif>{{$cartCount}}</span></a>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light rounded-pill dropdown-toggle d-flex align-items-center gap-2 px-3" data-bs-toggle="dropdown" type="button">
                            <span class="avatar-dot"><i class="bi bi-person-circle"></i></span><span class="d-none d-lg-inline">{{auth()->user()->name}}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow rounded-4 p-2">
                            <li><a class="dropdown-item rounded-3" href="{{route('member.profile')}}"><i class="bi bi-person me-2"></i>{{__('My Account')}}</a></li>
                            <li><a class="dropdown-item rounded-3" href="{{route('member.orders')}}"><i class="bi bi-bag-check me-2"></i>{{__('My Purchases')}}</a></li>
                            @if(auth()->user()->role!='member')<li><a class="dropdown-item rounded-3" href="{{route('admin.dashboard')}}"><i class="bi bi-speedometer2 me-2"></i>{{__('Backoffice')}}</a></li>@endif
                            <li><hr class="dropdown-divider"></li>
                            <li><form method="post" action="{{route('logout')}}">@csrf<button class="dropdown-item rounded-3 text-danger"><i class="bi bi-box-arrow-right me-2"></i>{{__('Logout')}}</button></form></li>
                        </ul>
                    </div>
                @else
                    <a class="nav-link" href="{{route('login')}}">{{__('Login')}}</a><a class="btn btn-sm btn-brand rounded-pill ms-lg-2 register-nav-btn" href="{{route('register')}}">{{__('Register')}}</a>
                @endauth
            </div>
        </div>
    </div>
</nav>
<main class="py-4">
    @if(session('success'))<div class="container"><div class="alert alert-success rounded-4 shadow-sm">{{session('success')}}</div></div>@endif
    @if(session('error'))<div class="container"><div class="alert alert-danger rounded-4 shadow-sm">{{session('error')}}</div></div>@endif
    @yield('content')
</main>
<div class="footer-top"><i class="bi bi-geo-alt me-2"></i> {{__('Rai Khaisaeng Strawberry Farm')}} {{__('Samoeng District, Chiang Mai')}}</div>
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">
            @php
                $companyPhone1Name = \App\Models\Setting::getValue('company_phone_1_name','คุณไขแสง');
                $companyPhone1 = \App\Models\Setting::getValue('company_phone_1','089-999-8295');
                $companyPhone2Name = \App\Models\Setting::getValue('company_phone_2_name','คุณอ๋อย');
                $companyPhone2 = \App\Models\Setting::getValue('company_phone_2','081-033-4893');
                $companyPhone3Name = \App\Models\Setting::getValue('company_phone_3_name','คุณตี๋');
                $companyPhone3 = \App\Models\Setting::getValue('company_phone_3','089-265-5685');
                $companyMapUrl = \App\Models\Setting::getValue('company_map_url','https://maps.app.goo.gl/GyHtBcHiVML1NudQ9');
                $companyMapEmbed = 'https://www.google.com/maps?q=18.854859,98.561256&z=16&output=embed';
            @endphp
            <div class="col-lg-3"><h5 class="fw-bold">{{__('Rai Khaisaeng Strawberry Farm')}}</h5><p class="mb-2">{{__('Rai Khaisaeng Strawberry Farm and Community Products')}}</p><p class="mb-1"><i class="bi bi-person"></i> {{$companyPhone1Name}} {{$companyPhone1}}</p><p class="mb-1"><i class="bi bi-person"></i> {{$companyPhone2Name}} {{$companyPhone2}}</p><p class="mb-3"><i class="bi bi-person"></i> {{$companyPhone3Name}} {{$companyPhone3}}</p><a class="btn btn-outline-light px-4" href="tel:{{preg_replace('/\D+/','',$companyPhone1)}}">{{__('Contact Us')}} <i class="bi bi-chevron-right"></i></a></div>
            <div class="col-lg-4"><h5 class="fw-bold">{{__('Map and Branch')}}</h5><p>{{__('Rai Khaisaeng Strawberry Farm')}} {{__('Bo Kaeo Subdistrict, Samoeng District, Chiang Mai')}}</p><iframe class="footer-map mb-2" loading="lazy" src="{{$companyMapEmbed}}"></iframe><a target="_blank" href="{{$companyMapUrl}}"><i class="bi bi-map me-2"></i> {{__('Open Google Maps')}}</a></div>
            <div class="col-lg-3"><h5 class="fw-bold">{{__('Help')}}</h5><p><a href="{{route('shop.products')}}">{{__('All Products')}}</a></p><p><a href="{{route('member.orders')}}">{{__('Orders')}}</a></p><p><a href="#">FAQ</a></p></div>
            <div class="col-lg-2"><h5 class="fw-bold">{{__('Contact Channels')}}</h5><div class="mt-3"><a class="social-circle social-facebook" href="{{$socialFacebook}}" target="_blank"><i class="bi bi-facebook"></i></a><a class="social-circle social-youtube" href="{{$socialYoutube}}" target="_blank"><i class="bi bi-youtube"></i></a><a class="social-circle social-line" href="{{$socialLine}}" target="_blank"><i class="bi bi-line"></i></a></div></div>
        </div>
        <hr class="border-light opacity-25 my-4"><div class="d-flex flex-wrap justify-content-between small"><span>© {{date('Y')}} {{__('Rai Khaisaeng Strawberry Farm')}}</span><span>{{__('Privacy Policy')}} | {{__('Customer Policy')}}</span></div>
    </div>
</footer>
<div id="cartToast" class="toast-cart alert alert-success shadow rounded-4"><i class="bi bi-check-circle me-2"></i><span>{{__('Add to Cart')}}</span></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('submit', async function(e){
    const form = e.target.closest('.js-add-to-cart');
    if(!form) return;
    e.preventDefault();
    const btn=form.querySelector('button'); const old=btn.innerHTML; btn.disabled=true; btn.innerHTML='<span class="spinner-border spinner-border-sm"></span> ...';
    try{
        const res=await fetch(form.action,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},body:new FormData(form)});
        if(res.status===401 || res.redirected){ window.location.href='{{route('login')}}'; return; }
        const data=await res.json();
        if(data.cart_count!==undefined){ const badge=document.getElementById('cartBadge'); badge.textContent=data.cart_count; badge.style.display='inline-block'; }
        const toast=document.getElementById('cartToast'); toast.style.display='block'; setTimeout(()=>toast.style.display='none',1800);
    }catch(err){ form.submit(); }
    finally{ btn.disabled=false; btn.innerHTML=old; }
});
</script>
<script src="{{ asset('js/responsive-v24.js') }}"></script>
@stack('scripts')
</body>
</html>
