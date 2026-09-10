@extends('layouts.app')
@section('content')
<div class="container profile-page">
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="profile-sidebar bg-white rounded-4 shadow-sm p-4 sticky-top" style="top:100px">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div class="profile-avatar"><i class="bi bi-person-fill"></i></div>
                    <div><div class="fw-bold">{{$user->name}}</div><div class="small text-muted">{{$user->email}}</div></div>
                </div>
                <a class="side-link active" href="#account"><i class="bi bi-person"></i> {{__('My Account')}}</a>
                <a class="side-link" href="#address"><i class="bi bi-geo-alt"></i> ที่อยู่ของฉัน</a>
                <a class="side-link" href="{{route('member.orders')}}"><i class="bi bi-bag-check"></i> {{__('My Purchases')}}</a>
                <a class="side-link" href="{{route('member.cart')}}"><i class="bi bi-cart3"></i> {{__('Shopping Cart')}}</a>
            </div>
        </div>
        <div class="col-lg-9">
            <div id="account" class="bg-white rounded-4 shadow-sm p-4 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="fw-bold mb-1">{{__('My Account')}}</h3><p class="text-muted mb-0">จัดการข้อมูลส่วนตัว {{__('Phone')}} และรหัสผ่าน</p></div><span class="badge rounded-pill text-bg-danger px-3 py-2">{{strtoupper($user->role)}}</span></div>
                @if($errors->any())<div class="alert alert-danger rounded-4"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{$e}}</li>@endforeach</ul></div>@endif
                <form method="post" action="{{route('member.profile.update')}}" class="row g-3">
                    @csrf @method('PATCH')
                    <div class="col-md-6"><label class="form-label fw-bold">{{__('Name')}}</label><input class="form-control rounded-pill" name="name" value="{{old('name',$user->name)}}" required></div>
                    <div class="col-md-6"><label class="form-label fw-bold">อีเมล</label><input type="email" class="form-control rounded-pill" name="email" value="{{old('email',$user->email)}}" required></div>
                    <div class="col-md-6"><label class="form-label fw-bold">{{__('Phone')}}</label><input class="form-control rounded-pill" name="phone" value="{{old('phone',$user->phone)}}" placeholder="เช่น 0899998295" inputmode="numeric" pattern="[0-9]{9,10}" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"></div>
                    <div class="col-md-6"><label class="form-label fw-bold">รหัสผ่านใหม่</label><input type="password" class="form-control rounded-pill" name="password" placeholder="เว้นว่างไว้หากไม่เปลี่ยน" autocomplete="new-password"></div>
                    <div class="col-md-6"><label class="form-label fw-bold">ยืนยันรหัสผ่านใหม่</label><input type="password" class="form-control rounded-pill" name="password_confirmation" autocomplete="new-password"></div>
                    <div class="col-12"><label class="form-label fw-bold">ที่อยู่หลักแบบย่อ</label><textarea class="form-control rounded-4" name="address" rows="3" placeholder="ใช้แสดงเป็นข้อมูลพื้นฐานของบัญชี">{{old('address',$user->address)}}</textarea></div>
                    <div class="col-12 text-end"><button class="btn btn-brand rounded-pill px-5"><i class="bi bi-save me-1"></i> บันทึกข้อมูล</button></div>
                </form>
            </div>
            <div id="address" class="bg-white rounded-4 shadow-sm p-4">
                <div class="d-flex justify-content-between align-items-center mb-3"><div><h3 class="fw-bold mb-1">ที่อยู่ของฉัน</h3><p class="text-muted mb-0">เลือก{{__('Province')}} → อำเภอ → ตำบล แล้วระบบจะเติม{{__('Postal Code')}}ให้อัตโนมัติ</p></div></div>
                <form method="post" action="{{route('member.addresses.store')}}" class="address-form rounded-4 p-3 mb-4" data-thai-address>
                    @csrf
                    <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle text-danger me-2"></i>เพิ่มที่อยู่ใหม่</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">{{__('Recipient Name')}}</label><input class="form-control rounded-pill" name="recipient_name" value="{{$user->name}}" required></div>
                        <div class="col-md-6"><label class="form-label">{{__('Phone')}}</label><input class="form-control rounded-pill" name="phone" value="{{$user->phone}}" required inputmode="numeric" pattern="[0-9]{9,10}" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"></div>
                        <div class="col-12"><label class="form-label">{{__('Address / House No. / Village / Alley / Road')}}</label><textarea class="form-control rounded-4" name="address" rows="2" required>{{$user->address}}</textarea></div>
                        <div class="col-md-3"><label class="form-label">{{__('Province')}}</label><select class="form-select rounded-pill" name="province" data-province required></select></div>
                        <div class="col-md-3"><label class="form-label">{{__('District')}}</label><select class="form-select rounded-pill" name="district" data-district required></select></div>
                        <div class="col-md-3"><label class="form-label">{{__('Subdistrict')}}</label><select class="form-select rounded-pill" name="subdistrict" data-subdistrict required></select></div>
                        <div class="col-md-3"><label class="form-label">{{__('Postal Code')}}</label><input class="form-control rounded-pill bg-light" name="postal_code" data-postal readonly required></div>
                        <div class="col-12 d-flex justify-content-between align-items-center"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_default" value="1"> ตั้งเป็นที่อยู่หลัก</label><button class="btn btn-outline-brand rounded-pill px-4">เพิ่มที่อยู่</button></div>
                    </div>
                </form>
                <div class="row g-3">
                    @forelse($user->addresses as $addr)
                    <div class="col-12"><div class="address-card rounded-4 p-3">
                        <form method="post" action="{{route('member.addresses.update',$addr)}}" class="row g-3 align-items-end" data-thai-address>
                            @csrf @method('PATCH')
                            <div class="col-md-6"><label class="form-label">{{__('Recipient Name')}}</label><input class="form-control rounded-pill" name="recipient_name" value="{{$addr->recipient_name}}" required></div>
                            <div class="col-md-6"><label class="form-label">{{__('Phone')}}</label><input class="form-control rounded-pill" name="phone" value="{{$addr->phone}}" required inputmode="numeric" pattern="[0-9]{9,10}" maxlength="10" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)"></div>
                            <div class="col-12"><label class="form-label">{{__('Address / House No. / Village / Alley / Road')}}</label><textarea class="form-control rounded-4" name="address" rows="2" required>{{$addr->address}}</textarea></div>
                            <div class="col-md-3"><label class="form-label">{{__('Province')}}</label><select class="form-select rounded-pill" name="province" data-province data-value="{{$addr->province}}" required></select></div>
                            <div class="col-md-3"><label class="form-label">{{__('District')}}</label><select class="form-select rounded-pill" name="district" data-district data-value="{{$addr->district}}" required></select></div>
                            <div class="col-md-3"><label class="form-label">{{__('Subdistrict')}}</label><select class="form-select rounded-pill" name="subdistrict" data-subdistrict data-value="{{$addr->subdistrict}}" required></select></div>
                            <div class="col-md-3"><label class="form-label">{{__('Postal Code')}}</label><input class="form-control rounded-pill bg-light" name="postal_code" data-postal data-value="{{$addr->postal_code}}" value="{{$addr->postal_code}}" readonly required></div>
                            <div class="col-md-6"><label class="form-check"><input class="form-check-input" type="checkbox" name="is_default" value="1" @checked($addr->is_default)> ตั้งเป็นที่อยู่หลัก @if($addr->is_default)<span class="badge text-bg-success ms-2">{{__('Default')}}</span>@endif</label></div>
                            <div class="col-md-6 text-md-end"><button class="btn btn-brand rounded-pill px-4">บันทึก</button></div>
                        </form>
                        <form method="post" action="{{route('member.addresses.destroy',$addr)}}" class="text-end mt-2" onsubmit="return confirm('ต้องการ{{__('Remove')}}ที่อยู่นี้ใช่ไหม')">@csrf @method('DELETE')<button class="btn btn-link text-danger text-decoration-none">{{__('Remove')}}ที่อยู่</button></form>
                    </div></div>
                    @empty
                    <div class="col-12"><div class="alert alert-warning rounded-4 mb-0">ยังไม่มีที่อยู่จัดส่ง เพิ่มที่อยู่ด้านบนเพื่อให้หน้า Checkout ดึงไปใช้ได้ทันที</div></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
<style>.profile-avatar{width:58px;height:58px;border-radius:50%;background:#ffe3e6;color:var(--brand);display:flex;align-items:center;justify-content:center;font-size:2rem}.side-link{display:block;padding:.85rem 1rem;border-radius:14px;text-decoration:none;color:#333;font-weight:700;margin:.2rem 0}.side-link i{color:var(--brand);margin-right:.5rem}.side-link:hover,.side-link.active{background:#fff3f4;color:var(--brand)}.address-form{background:#fff8f8;border:1px dashed #efb5bd}.address-card{border:1px solid #edf0f3;background:#fff;box-shadow:0 6px 18px rgba(0,0,0,.04)}</style>
@endsection
@push('scripts')<script src="{{asset('js/thai-address.js')}}"></script>@endpush
