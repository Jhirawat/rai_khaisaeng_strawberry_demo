@php
    $adminThemeDefaults = [
        'admin_sidebar_start'=>'#2461F0','admin_sidebar_end'=>'#EFA9F4','admin_accent'=>'#D94B5B',
        'admin_background'=>'#F5F7F6','admin_card'=>'#FFFFFF','admin_text'=>'#13231F'
    ];
    $adminTheme = $adminThemeDefaults;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            foreach ($adminThemeDefaults as $k => $v) {
                $adminTheme[$k] = optional(\App\Models\Setting::where('key',$k)->first())->value ?: $v;
            }
        }
    } catch (\Throwable $e) { $adminTheme = $adminThemeDefaults; }
    $faviconPath = 'favicon.png';
    $adminLogoPath = 'images/logo-nav-en.png';
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
            $faviconPath = optional(\App\Models\Setting::where('key','site_favicon')->first())->value ?: 'favicon.png';
            $adminLogoPath = optional(\App\Models\Setting::where('key','site_logo_en')->first())->value ?: 'images/logo-nav-en.png';
        }
    } catch (\Throwable $e) { $faviconPath = 'favicon.png'; $adminLogoPath = 'images/logo-nav-en.png'; }

    $pendingOrderCount = 0;
    $pendingPaymentCount = 0;
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('orders')) {
            $pendingOrderCount = \App\Models\Order::whereIn('status', ['pending_payment','paid','preparing'])->count();
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('payments')) {
            $pendingPaymentCount = \App\Models\Payment::where('status', 'pending')->count();
        }
    } catch (\Throwable $e) { $pendingOrderCount = 0; $pendingPaymentCount = 0; }
@endphp
<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin - Rai Khaisaeng Strawberry</title>
    <link rel="icon" type="image/png" href="{{ asset($faviconPath) }}">
    <link rel="shortcut icon" href="{{ asset($faviconPath) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800;900&family=Kanit:wght@300;400;500;600;700;800;900&family=Bai+Jamjuree:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700;800;900&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="{{ asset('css/responsive-v24.css') }}">
    <style>
        :root{--admin-primary:{{$adminTheme['admin_sidebar_start']}};--admin-primary-dark:{{$adminTheme['admin_sidebar_end']}};--admin-accent:{{$adminTheme['admin_accent']}};--admin-bg:{{$adminTheme['admin_background']}};--admin-card:{{$adminTheme['admin_card']}};--admin-text:{{$adminTheme['admin_text']}};--admin-soft:#eef5f2}
        body{background:var(--admin-bg);font-family:'Prompt','Inter','Kanit','Bai Jamjuree','Roboto','Segoe UI',Tahoma,sans-serif;color:var(--admin-text)}.admin-sidebar{width:280px;min-height:100vh;background:linear-gradient(180deg,var(--admin-primary),var(--admin-primary-dark));position:sticky;top:0}.admin-sidebar a{color:#f7fffb;text-decoration:none;border-radius:14px;padding:.72rem 1rem;display:flex;align-items:center;gap:.35rem;margin:.22rem 0;font-weight:650;position:relative}.admin-sidebar a .nav-text{flex:1}.admin-notify-badge{background:#fff;color:var(--admin-accent);border-radius:999px;min-width:1.45rem;height:1.45rem;padding:0 .35rem;display:inline-flex;align-items:center;justify-content:center;font-size:.76rem;font-weight:900;box-shadow:0 3px 9px rgba(0,0,0,.15)}.admin-sidebar a:hover,.admin-sidebar a.active{background:rgba(255,255,255,.14)}.brand-admin{font-weight:900;font-size:1.35rem}.admin-logo{width:120px;max-width:100%;height:auto;display:block;margin-bottom:.45rem}.content-card{background:var(--admin-card);border-radius:20px;box-shadow:0 10px 32px rgba(28,65,54,.07)}.admin-topbar{background:var(--admin-card);border-radius:20px;box-shadow:0 10px 32px rgba(28,65,54,.06);padding:1rem 1.25rem}.btn-danger,.bg-danger{background:var(--admin-accent)!important;border-color:var(--admin-accent)!important}.text-danger{color:var(--admin-accent)!important}.badge-admin{background:#e5f1ed;color:var(--admin-primary);border:1px solid #c7ded5}.btn-admin{background:var(--admin-primary);border-color:var(--admin-primary);color:#fff}.btn-admin:hover{background:var(--admin-primary-dark);border-color:var(--admin-primary-dark);color:#fff}.metric-card{border-left:6px solid var(--admin-primary)}.metric-card.warning{border-color:#f4b000}.metric-card.danger{border-color:var(--admin-accent)}.metric-card.info{border-color:#38a3c5}.content-card,.admin-topbar,.metric-card{animation:adminFadeUp .38s ease both}@keyframes adminFadeUp{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}.admin-sidebar a{transition:background .18s ease,transform .18s ease}.admin-sidebar a:hover{transform:translateX(3px)}@media (prefers-reduced-motion: reduce){*,*::before,*::after{transition:none!important;animation:none!important}}@media(max-width:900px){.admin-sidebar{width:220px}}
    </style>
</head>
<body>
<div class="admin-backdrop" data-admin-sidebar-backdrop></div>
<div class="d-flex">
    <aside class="admin-sidebar text-white p-3">
        <img class="admin-logo" src="{{ asset($adminLogoPath) }}" alt="Rai Khaisaeng Strawberry"><div class="brand-admin mb-1"><i class="bi bi-speedometer2 me-2"></i>SB Admin</div><p class="opacity-75 mb-4">Rai Khaisaeng Strawberry</p>
        <a href="{{route('admin.dashboard')}}"><i class="bi bi-house me-2"></i><span class="nav-text">Dashboard</span></a>
        <a href="{{route('admin.products.index')}}"><i class="bi bi-box-seam me-2"></i><span class="nav-text">สินค้า</span></a>
        <a href="{{route('admin.categories.index')}}"><i class="bi bi-tags me-2"></i><span class="nav-text">หมวดหมู่</span></a>
        <a href="{{route('admin.orders.index')}}"><i class="bi bi-receipt me-2"></i><span class="nav-text">ออเดอร์</span>@if($pendingOrderCount>0)<span class="admin-notify-badge">{{$pendingOrderCount}}</span>@endif</a>
        <a href="{{route('admin.payments.index')}}"><i class="bi bi-credit-card me-2"></i><span class="nav-text">ชำระเงิน</span>@if($pendingPaymentCount>0)<span class="admin-notify-badge">{{$pendingPaymentCount}}</span>@endif</a>
        <a href="{{route('admin.inventory.index')}}"><i class="bi bi-archive me-2"></i><span class="nav-text">คลังสินค้า</span></a>
        <a href="{{route('admin.reports.index')}}"><i class="bi bi-bar-chart me-2"></i><span class="nav-text">รายงาน</span></a>
        @if((auth()->user()->role ?? null) === 'super_admin')
            <a href="{{route('admin.users.index')}}"><i class="bi bi-people me-2"></i><span class="nav-text">สมาชิก / สิทธิ์ผู้ใช้งาน</span></a>
            <a href="{{route('admin.theme.edit')}}"><i class="bi bi-palette me-2"></i><span class="nav-text">ปรับแต่งธีม</span></a>
            <a href="{{route('admin.company-settings.edit')}}"><i class="bi bi-gear me-2"></i><span class="nav-text">ตั้งค่าข้อมูลร้านค้า</span></a>
        @endif
        <hr class="border-light opacity-25"><a href="{{route('shop.home')}}"><i class="bi bi-shop me-2"></i><span class="nav-text">หน้าร้าน</span></a>
        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="w-100 border-0 bg-transparent text-start text-white" style="border-radius:14px;padding:.72rem 1rem;font-weight:650">
                <i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ
            </button>
        </form>
    </aside>
    <main class="flex-fill p-4">
        <div class="admin-topbar d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2"><button type="button" class="btn btn-admin rounded-pill mobile-admin-menu" data-admin-sidebar-toggle><i class="bi bi-list"></i></button><h3 class="mb-0 fw-bold">@yield('title','Dashboard')</h3></div>
            <div class="dropdown">
                <button class="btn badge-admin rounded-pill px-3 py-2 dropdown-toggle fw-bold" data-bs-toggle="dropdown" type="button">
                    <i class="bi bi-person-circle me-1"></i>{{auth()->user()->name ?? ''}}
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow rounded-4">
                    <li><span class="dropdown-item-text small text-muted">{{auth()->user()->email ?? ''}}</span></li>
                    <li><a class="dropdown-item" href="{{ route('shop.home') }}"><i class="bi bi-shop me-2"></i>ไปหน้าร้าน</a></li>
                    <li><a class="dropdown-item" href="{{ route('login') }}"><i class="bi bi-arrow-repeat me-2"></i>สลับบัญชี</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
        @if(session('success'))<div class="alert alert-success rounded-4 shadow-sm">{{session('success')}}</div>@endif
        @yield('content')
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('js/responsive-v24.js') }}"></script>
</body></html>
