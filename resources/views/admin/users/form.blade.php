@extends('layouts.admin')
@section('title', $user->exists ? 'แก้ไขผู้ใช้งาน' : 'เพิ่มผู้ใช้งานใหม่')
@section('content')
<style>.edit-card{border:0;border-radius:22px;box-shadow:0 10px 30px rgba(20,30,60,.07)}.sticky-actions{position:sticky;bottom:0;background:rgba(255,255,255,.92);backdrop-filter:blur(8px);border-top:1px solid #eee;padding:1rem;margin:1rem -1rem -1rem;border-radius:0 0 22px 22px}.avatar-large{width:88px;height:88px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#ffe5e9;color:#dc3545;font-size:2rem;font-weight:900}</style>
<form method="post" action="{{ $user->exists ? route('admin.users.update',$user) : route('admin.users.store') }}">
  @csrf @if($user->exists) @method('put') @endif
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card edit-card p-4 mb-4">
        <h4 class="fw-bold mb-3"><i class="bi bi-person-vcard text-danger me-2"></i>ข้อมูลส่วนตัว</h4>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">ชื่อ-นามสกุล</label><input name="name" class="form-control rounded-pill" value="{{ old('name',$user->name) }}" required></div>
          <div class="col-md-6"><label class="form-label">อีเมล</label><input name="email" type="email" class="form-control rounded-pill" value="{{ old('email',$user->email) }}" required></div>
          <div class="col-md-6"><label class="form-label">เบอร์โทร</label><input name="phone" class="form-control rounded-pill" value="{{ old('phone',$user->phone) }}"></div>
          <div class="col-md-6"><label class="form-label">รหัสผ่าน {{ $user->exists ? '(เว้นว่างถ้าไม่เปลี่ยน)' : '' }}</label><input name="password" type="password" class="form-control rounded-pill" {{ $user->exists ? '' : 'required' }}></div>
          <div class="col-12"><label class="form-label">ที่อยู่หลัก / ที่อยู่จัดส่ง</label><textarea name="address" class="form-control rounded-4" rows="5">{{ old('address',$user->address) }}</textarea></div>
        </div>
      </div>
      @if($user->exists)
      <div class="card edit-card p-4">
        <h4 class="fw-bold mb-3"><i class="bi bi-clock-history text-danger me-2"></i>กิจกรรมล่าสุด</h4>
        <div class="table-responsive"><table class="table"><tr><th>รายการ</th><th>เวลา</th><th>หมายเหตุ</th></tr><tr><td>อัปเดตบัญชีล่าสุด</td><td>{{ optional($user->updated_at)->format('d/m/Y H:i') }}</td><td>ระบบใช้ข้อมูลนี้แทน Last Active เบื้องต้น</td></tr><tr><td>สมัครสมาชิก</td><td>{{ optional($user->created_at)->format('d/m/Y H:i') }}</td><td>-</td></tr></table></div>
      </div>
      @endif
    </div>
    <div class="col-lg-4">
      <div class="card edit-card p-4 mb-4">
        <div class="d-flex align-items-center gap-3 mb-4"><div class="avatar-large">{{ $user->exists ? mb_substr($user->name,0,1) : '+' }}</div><div><div class="fw-bold">{{ $user->name ?: 'ผู้ใช้งานใหม่' }}</div><div class="text-muted small">{{ $user->email }}</div></div></div>
        <h5 class="fw-bold">สถานะบัญชี</h5>
        <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active',$user->is_active ?? true))><label class="form-check-label">เปิดใช้งานบัญชี</label></div>
        <h5 class="fw-bold mt-4">Role & Permissions</h5>
        <select name="role" class="form-select rounded-pill mb-3" required>@foreach(['member'=>'Member','staff'=>'Staff','admin'=>'Admin','super_admin'=>'Super Admin'] as $k=>$v)<option value="{{$k}}" @selected(old('role',$user->role)===$k)>{{$v}}</option>@endforeach</select>
        <div class="alert alert-warning rounded-4 small">การเปลี่ยน Role มีผลต่อสิทธิ์เข้าถึงหน้า Admin ทันที โปรดตรวจสอบก่อนบันทึก</div>
        @if($user->exists)<a href="{{ route('admin.users.show',$user) }}" class="btn btn-outline-secondary rounded-pill w-100">ไปหน้าความปลอดภัย/รีเซ็ตรหัสผ่าน</a>@endif
      </div>
    </div>
  </div>
  <div class="sticky-actions d-flex justify-content-end gap-2"><a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary rounded-pill px-4">ยกเลิก</a><button class="btn btn-danger rounded-pill px-4">บันทึกข้อมูล</button></div>
</form>
@endsection
