@extends('layouts.admin')
@section('title','ปรับแต่งธีม')
@section('content')
<style>
    .theme-section-card{border:1px solid #e6ece9;border-radius:22px;background:#fff;box-shadow:0 10px 30px rgba(0,0,0,.04)}
    .theme-group-title{font-weight:900;font-size:1.1rem;margin-bottom:.25rem}.theme-group-hint{color:#6b7280;font-size:.92rem;margin-bottom:1rem}
    .color-input-card{border:1px solid #e6ece9;border-radius:18px;padding:16px;background:#fff;height:100%}
    .color-input-card input[type=color]{width:56px;height:44px;border:0;background:transparent;padding:0}.preset-card{border:1px solid #e2e8e5;border-radius:18px;padding:14px;background:#fff;transition:.2s}.preset-card:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(0,0,0,.08)}
    .preview-admin-sidebar{min-height:360px;border-radius:22px;background:linear-gradient(180deg, var(--preview-admin-start), var(--preview-admin-end));color:white;padding:22px;box-shadow:0 18px 50px rgba(0,0,0,.12)}
    .preview-admin-link{padding:12px 14px;border-radius:14px;margin:8px 0;background:rgba(255,255,255,.10);font-weight:700}.preview-admin-link.active{background:rgba(255,255,255,.20)}
    .preview-shop{border-radius:22px;overflow:hidden;background:var(--preview-shop-bg);box-shadow:0 18px 50px rgba(0,0,0,.10);border:1px solid #eef2f0}
    .preview-shop-nav{background:var(--preview-shop-card);color:var(--preview-shop-nav-text);padding:14px 18px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid #edf0ef}.preview-shop-brand{font-weight:900;color:var(--preview-shop-nav-text)}
    .preview-shop-hero{margin:18px;border-radius:20px;padding:26px;color:#fff;background:linear-gradient(120deg,color-mix(in srgb,var(--preview-shop-brand) 88%,transparent),color-mix(in srgb,var(--preview-shop-brand) 52%,white));min-height:160px}.preview-shop-card{margin:0 18px 18px;background:var(--preview-shop-card);border-radius:18px;padding:14px;box-shadow:0 8px 22px rgba(0,0,0,.08)}
    .preview-shop-footer-top{background:var(--preview-shop-cream);color:var(--preview-shop-footer);padding:10px 18px;text-align:center;font-weight:800}.preview-shop-footer{background:var(--preview-shop-footer);color:#fff;padding:18px}.color-badge{width:28px;height:28px;border-radius:50%;display:inline-block;border:2px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.12);vertical-align:middle}
</style>
<div class="content-card p-4 mb-4">
    <div class="d-flex justify-content-between gap-3 flex-wrap align-items-start">
        <div>
            <div class="text-muted mb-1">หน้าหลัก &gt; ตั้งค่าระบบ &gt; ปรับแต่งธีม</div>
            <h1 class="fw-black mb-1">ปรับแต่งสีระบบ</h1>
            <p class="text-muted mb-0">แยกปรับสีเป็นสัดส่วน ระหว่างหลังบ้าน Admin/Super Admin และหน้าร้าน Guest/Member พร้อมตัวอย่างก่อนบันทึก</p>
        </div>
        <form method="post" action="{{ route('admin.theme.reset') }}">@csrf<button class="btn btn-outline-secondary rounded-pill px-4" onclick="return confirm('รีเซ็ตสีเป็นค่าเริ่มต้นใช่ไหม?')"><i class="bi bi-arrow-counterclockwise me-1"></i>รีเซ็ตสี</button></form>
    </div>
</div>

<form method="post" action="{{ route('admin.theme.update') }}">
@csrf @method('PATCH')
@if($errors->any())<div class="alert alert-danger rounded-4">กรุณาตรวจสอบค่าสีให้ถูกต้อง เช่น #2F5D50</div>@endif
<div class="row g-4">
    <div class="col-lg-7">
        <div class="theme-section-card p-4 mb-4">
            <div class="theme-group-title"><i class="bi bi-speedometer2 me-2 text-danger"></i>สีฝั่ง Admin / Super Admin</div>
            <div class="theme-group-hint">ใช้กับ Sidebar, ปุ่ม, พื้นหลัง, การ์ด และตัวอักษรของระบบหลังบ้าน</div>
            <div class="row g-3">
                @php
                    $adminFields = [
                        'admin_sidebar_start' => ['สี Sidebar ด้านบน','สีเริ่มต้นของแถบเมนูหลังบ้าน'],
                        'admin_sidebar_end' => ['สี Sidebar ด้านล่าง','สีปลาย Gradient ของ Sidebar'],
                        'admin_accent' => ['สีปุ่มหลัก / จุดเด่น','ใช้กับปุ่มบันทึก ป้าย และตัวเลขสำคัญ'],
                        'admin_background' => ['สีพื้นหลังหลังบ้าน','แนะนำสีอ่อนเพื่อลดอาการล้าตา'],
                        'admin_card' => ['สีการ์ดข้อมูล','สีพื้นของกล่องข้อมูล'],
                        'admin_text' => ['สีตัวอักษรหลัก','ควรเป็นสีเข้ม อ่านง่าย'],
                    ];
                @endphp
                @foreach($adminFields as $key => [$label,$hint])
                    <div class="col-md-6">
                        <div class="color-input-card">
                            <label class="fw-bold d-block mb-1">{{ $label }}</label>
                            <div class="text-muted small mb-2">{{ $hint }}</div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" value="{{ $theme[$key] }}" data-color-target="{{ $key }}">
                                <input id="{{ $key }}" name="{{ $key }}" class="form-control rounded-pill js-color-field" value="{{ old($key,$theme[$key]) }}" maxlength="7">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="theme-section-card p-4">
            <div class="theme-group-title"><i class="bi bi-shop-window me-2 text-danger"></i>สีฝั่ง Guest / Member</div>
            <div class="theme-group-hint">ใช้กับ Navbar หน้าร้าน ปุ่ม ตะกร้า สินค้า Checkout Footer และแถบข้อมูลด้านล่าง</div>
            <div class="row g-3">
                @php
                    $shopFields = [
                        'shop_brand' => ['สีหลักหน้าร้าน','ใช้กับโลโก้ ปุ่ม ตะกร้า และหัวข้อเด่น'],
                        'shop_brand_dark' => ['สีหลักเข้ม','ใช้ตอน Hover และปุ่มเข้ม'],
                        'shop_soft' => ['สีพื้นอ่อน','ใช้กับพื้นหลังรูปสินค้า/พื้นที่อ่อน'],
                        'shop_background' => ['สีพื้นหลังหน้าร้าน','พื้นหลังของหน้า Guest/Member'],
                        'shop_card' => ['สีการ์ดหน้าร้าน','สีพื้นการ์ดสินค้า/กล่องข้อมูล'],
                        'shop_text' => ['สีตัวอักษรหน้าร้าน','สีข้อความหลัก'],
                        'shop_footer' => ['สี Footer','สีพื้นหลัง Footer'],
                        'shop_footer_dark' => ['สี Footer เข้ม','สีเข้มเสริมของ Footer'],
                        'shop_cream' => ['สีแถบข้อมูล','แถบที่อยู่/แถบประกาศด้านบน Footer'],
                        'shop_nav_text' => ['สีตัวอักษร Navbar/โลโก้','แก้สีข้อความที่อยู่บนแถบเมนูหน้าร้าน'],
                    ];
                @endphp
                @foreach($shopFields as $key => [$label,$hint])
                    <div class="col-md-6">
                        <div class="color-input-card">
                            <label class="fw-bold d-block mb-1">{{ $label }}</label>
                            <div class="text-muted small mb-2">{{ $hint }}</div>
                            <div class="d-flex align-items-center gap-2">
                                <input type="color" value="{{ $theme[$key] }}" data-color-target="{{ $key }}">
                                <input id="{{ $key }}" name="{{ $key }}" class="form-control rounded-pill js-color-field" value="{{ old($key,$theme[$key]) }}" maxlength="7">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="content-card p-4 position-sticky" style="top:24px">
            <h4 class="fw-bold mb-3"><i class="bi bi-eye me-2 text-danger"></i>ผลการแสดงตัวอย่าง</h4>
            <ul class="nav nav-pills mb-3" id="previewTab" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link active rounded-pill" data-bs-toggle="pill" data-bs-target="#adminPreviewPane" type="button">หลังบ้าน</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link rounded-pill" data-bs-toggle="pill" data-bs-target="#shopPreviewPane" type="button">หน้าร้าน</button></li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane fade show active" id="adminPreviewPane">
                    <div id="adminPreview" class="preview-admin-sidebar" style="--preview-admin-start:{{ $theme['admin_sidebar_start'] }};--preview-admin-end:{{ $theme['admin_sidebar_end'] }}">
                        <div class="fs-4 fw-black mb-1"><i class="bi bi-speedometer2 me-2"></i>SB Admin</div>
                        <div class="opacity-75 mb-4">Rai Khaisaeng Strawberry</div>
                        <div class="preview-admin-link active"><i class="bi bi-house me-2"></i>Dashboard</div>
                        <div class="preview-admin-link"><i class="bi bi-box-seam me-2"></i>สินค้า</div>
                        <div class="preview-admin-link"><i class="bi bi-receipt me-2"></i>ออเดอร์</div>
                        <div class="preview-admin-link"><i class="bi bi-palette me-2"></i>ปรับแต่งธีม</div>
                    </div>
                    <div id="adminMiniCard" class="mt-3 p-3 rounded-4" style="background:{{ $theme['admin_background'] }};color:{{ $theme['admin_text'] }}">
                        <div class="fw-bold mb-2">ตัวอย่างปุ่มหลังบ้าน</div>
                        <button type="button" class="btn rounded-pill px-4 text-white" id="adminAccentPreview" style="background:{{ $theme['admin_accent'] }};border-color:{{ $theme['admin_accent'] }}">บันทึกข้อมูล</button>
                    </div>
                </div>
                <div class="tab-pane fade" id="shopPreviewPane">
                    <div id="shopPreview" class="preview-shop" style="--preview-shop-brand:{{ $theme['shop_brand'] }};--preview-shop-brand-dark:{{ $theme['shop_brand_dark'] }};--preview-shop-bg:{{ $theme['shop_background'] }};--preview-shop-card:{{ $theme['shop_card'] }};--preview-shop-text:{{ $theme['shop_text'] }};--preview-shop-footer:{{ $theme['shop_footer'] }};--preview-shop-cream:{{ $theme['shop_cream'] }};--preview-shop-nav-text:{{ $theme['shop_nav_text'] ?? '#13231F' }}">
                        <div class="preview-shop-nav"><div class="preview-shop-brand"><i class="bi bi-shop-window me-1"></i>ไร่ไขแสงสตรอเบอร์รี่</div><button type="button" class="btn btn-sm rounded-pill text-white" id="shopAccentPreview" style="background:{{ $theme['shop_brand'] }}">ตะกร้า</button></div>
                        <div class="preview-shop-hero"><div class="small bg-white text-dark rounded-pill px-3 py-1 d-inline-block mb-2">หน้าร้าน Guest / Member</div><h4 class="fw-black mb-1">สตรอว์เบอร์รี่สดใหม่</h4><p class="mb-0">ตัวอย่าง Banner และปุ่มหน้าร้าน</p></div>
                        <div class="preview-shop-card" style="color:{{ $theme['shop_text'] }}"><div class="fw-bold">การ์ดสินค้า</div><div class="text-muted small">ราคา / ปุ่ม / สีพื้นหลัง</div><span class="color-badge mt-2" id="shopColorBadge" style="background:{{ $theme['shop_brand'] }}"></span></div>
                        <div class="preview-shop-footer-top">ไร่ไขแสงสตรอเบอร์รี่ อำเภอสะเมิง จังหวัดเชียงใหม่</div>
                        <div class="preview-shop-footer">Footer / ช่องทางติดต่อ</div>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-4">
                <button class="btn btn-admin rounded-pill px-5 py-2"><i class="bi bi-check2-square me-1"></i>บันทึกธีม</button>
            </div>
        </div>
    </div>
</div>
</form>

<div class="content-card p-4 mt-4">
    <h4 class="fw-bold mb-3"><i class="bi bi-palette2 me-2 text-danger"></i>ธีมสำเร็จรูป</h4>
    <p class="text-muted">เลือกแล้วจะเปลี่ยนทั้งฝั่งหลังบ้านและหน้าร้านพร้อมกัน</p>
    <div class="row g-3">
        @foreach($presets as $key => $preset)
            <div class="col-md-3">
                <form method="post" action="{{ route('admin.theme.preset',$key) }}" class="preset-card h-100">
                    @csrf
                    <div class="rounded-4 mb-3" style="height:80px;background:linear-gradient(135deg,{{ $preset['admin_sidebar_start'] }},{{ $preset['admin_sidebar_end'] }});"></div>
                    <div class="d-flex gap-1 mb-2">
                        <span class="color-badge" style="background:{{ $preset['admin_accent'] }}"></span>
                        <span class="color-badge" style="background:{{ $preset['shop_brand'] }}"></span>
                        <span class="color-badge" style="background:{{ $preset['shop_footer'] }}"></span>
                    </div>
                    <div class="fw-bold mb-2">{{ $preset['name'] }}</div>
                    <button class="btn btn-outline-dark rounded-pill btn-sm w-100">ใช้ธีมนี้</button>
                </form>
            </div>
        @endforeach
    </div>
</div>

<script>
    document.querySelectorAll('input[type=color][data-color-target]').forEach(color => {
        const input = document.getElementById(color.dataset.colorTarget);
        const sync = () => { input.value = color.value.toUpperCase(); updatePreview(); };
        color.addEventListener('input', sync);
        input.addEventListener('input', () => { if(/^#[0-9A-Fa-f]{6}$/.test(input.value)){ color.value = input.value; updatePreview(); } });
    });
    function updatePreview(){
        const get = id => document.getElementById(id).value;
        document.getElementById('adminPreview').style.setProperty('--preview-admin-start', get('admin_sidebar_start'));
        document.getElementById('adminPreview').style.setProperty('--preview-admin-end', get('admin_sidebar_end'));
        const adminBtn = document.getElementById('adminAccentPreview'); adminBtn.style.background = get('admin_accent'); adminBtn.style.borderColor = get('admin_accent');
        const adminCard = document.getElementById('adminMiniCard'); adminCard.style.background = get('admin_background'); adminCard.style.color = get('admin_text');
        const shop = document.getElementById('shopPreview');
        shop.style.setProperty('--preview-shop-brand', get('shop_brand'));
        shop.style.setProperty('--preview-shop-brand-dark', get('shop_brand_dark'));
        shop.style.setProperty('--preview-shop-bg', get('shop_background'));
        shop.style.setProperty('--preview-shop-card', get('shop_card'));
        shop.style.setProperty('--preview-shop-text', get('shop_text'));
        shop.style.setProperty('--preview-shop-footer', get('shop_footer'));
        shop.style.setProperty('--preview-shop-cream', get('shop_cream'));
        document.getElementById('shopAccentPreview').style.background = get('shop_brand');
        document.getElementById('shopColorBadge').style.background = get('shop_brand');
    }
</script>
@endsection
