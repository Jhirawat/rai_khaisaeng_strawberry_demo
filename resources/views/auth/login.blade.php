@extends('layouts.app')
@section('content')
<div class="container" style="max-width:760px">
    <div class="row g-4 align-items-stretch">
        <div class="col-lg-7">
            <div class="bg-white rounded-4 shadow-sm p-4 h-100">
                <h2 class="fw-bold mb-3">{{__('Login')}}</h2>
                @if($errors->any())<div class="alert alert-danger rounded-4">{{$errors->first()}}</div>@endif
                <form method="post" class="mb-3">@csrf
                    <input class="form-control rounded-pill mb-2" name="email" type="email" placeholder="Email" required>
                    <input class="form-control rounded-pill mb-2" name="password" type="password" placeholder="Password" required>
                    <button class="btn btn-danger rounded-pill w-100">{{__('Login')}}</button>
                </form>
                <div class="text-center text-muted my-3">{{__('or login with')}}</div>
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-danger rounded-pill" href="{{route('social.redirect','google')}}"><i class="bi bi-google me-2"></i> Google</a>
                    <a class="btn btn-outline-primary rounded-pill" href="{{route('social.redirect','facebook')}}"><i class="bi bi-facebook me-2"></i> Facebook</a>
                    <a class="btn btn-outline-success rounded-pill" href="{{route('social.redirect','line')}}"><i class="bi bi-line me-2"></i> LINE</a>
                </div>
                <p class="small text-muted mt-3 mb-0">* Social Login ใช้ได้เมื่อใส่ Client ID / Secret ในไฟล์ <code>.env</code> แล้ว</p>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="bg-light rounded-4 p-4 h-100 border">
                <h5 class="fw-bold mb-3"><i class="bi bi-person-check me-2"></i>บัญชีสำหรับทดสอบระบบ</h5>
                <div class="mb-3">
                    <span class="badge bg-success rounded-pill mb-1">Member</span>
                    <div class="small fw-semibold">user_test@khaisaeng.test</div>
                    <div class="small text-muted">password: <code>password</code></div>
                    <div class="small text-muted">ใช้ทดสอบหน้าร้าน ตะกร้า Checkout และใบเสร็จของตัวเอง</div>
                </div>
                <div class="mb-3">
                    <span class="badge bg-primary rounded-pill mb-1">Admin</span>
                    <div class="small fw-semibold">admin_test@khaisaeng.test</div>
                    <div class="small text-muted">password: <code>password</code></div>
                    <div class="small text-muted">ใช้ทดสอบงานประจำวัน เช่น สินค้า ออเดอร์ ชำระเงิน คลังสินค้า รายงาน</div>
                </div>
                <div class="mb-0">
                    <span class="badge bg-dark rounded-pill mb-1">Super Admin</span>
                    <div class="small fw-semibold">sbadmin_test@khaisaeng.test</div>
                    <div class="small text-muted">password: <code>password</code></div>
                    <div class="small text-muted">ใช้ทดสอบตั้งค่าระบบ ธีม ข้อมูลร้านค้า และจัดการผู้ใช้งาน</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
