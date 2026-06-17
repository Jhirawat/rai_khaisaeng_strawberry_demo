@extends('layouts.admin')
@section('title','จัดการผู้ใช้งาน')
@section('content')
<style>
.user-card{border:0;border-radius:22px;box-shadow:0 10px 30px rgba(20,30,60,.07)}
.user-stat{background:#fff;border-radius:18px;padding:1.1rem 1.25rem;box-shadow:0 8px 25px rgba(20,30,60,.06);border-left:6px solid #dc3545}.user-stat .num{font-size:2rem;font-weight:900}.avatar-circle{width:46px;height:46px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;background:#ffe5e9;color:#dc3545;font-weight:900}.role-badge{border-radius:999px;padding:.42rem .7rem;font-weight:800}.role-super_admin{background:#dc3545;color:#fff}.role-admin{background:#ff6b6b;color:#fff}.role-staff{background:#0d6efd;color:#fff}.role-member{background:#198754;color:#fff}.status-pill{border-radius:999px;padding:.38rem .75rem;font-weight:800}.table thead th{white-space:nowrap}.table td{vertical-align:middle}.action-stack{display:flex;gap:.4rem;flex-wrap:wrap}.btn-soft{border:1px solid #dee2e6;background:#fff;border-radius:999px;padding:.38rem .75rem}
</style>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <div class="text-muted">หน้าหลัก &gt; จัดการผู้ใช้งาน</div>
    <h2 class="fw-bold mb-1">จัดการผู้ใช้งาน</h2>
    <p class="text-muted mb-0">ตรวจสอบสิทธิ์ ค้นหา ระงับบัญชี และรีเซ็ตรหัสผ่านจากหน้าเดียว</p>
  </div>
  <a href="{{ route('admin.users.create') }}" class="btn btn-danger rounded-pill px-4 py-2"><i class="bi bi-person-plus me-1"></i> เพิ่มผู้ใช้งานใหม่</a>
</div>
<div class="row g-3 mb-4">
  <div class="col-md"><div class="user-stat"><div class="text-muted">ผู้ใช้งานทั้งหมด</div><div class="num">{{ $summary['total'] }}</div></div></div>
  <div class="col-md"><div class="user-stat" style="border-left-color:#198754"><div class="text-muted">ใช้งานปกติ</div><div class="num text-success">{{ $summary['active'] }}</div></div></div>
  <div class="col-md"><div class="user-stat" style="border-left-color:#6c757d"><div class="text-muted">ถูกระงับ</div><div class="num text-secondary">{{ $summary['suspended'] }}</div></div></div>
  <div class="col-md"><div class="user-stat" style="border-left-color:#0d6efd"><div class="text-muted">Admin/Super Admin</div><div class="num text-primary">{{ $summary['admins'] }}</div></div></div>
  <div class="col-md"><div class="user-stat" style="border-left-color:#ffc107"><div class="text-muted">Member</div><div class="num text-warning">{{ $summary['members'] }}</div></div></div>
</div>
<form class="card user-card p-3 mb-4" method="get">
  <div class="row g-3 align-items-end">
    <div class="col-lg-5"><label class="form-label fw-semibold">ค้นหา</label><input class="form-control rounded-pill" name="q" value="{{ request('q') }}" placeholder="ชื่อ / อีเมล / เบอร์โทร / User ID"></div>
    <div class="col-lg-3"><label class="form-label fw-semibold">บทบาท</label><select name="role" class="form-select rounded-pill"><option value="">ทุกบทบาท</option>@foreach(['member'=>'Member','staff'=>'Staff','admin'=>'Admin','super_admin'=>'Super Admin'] as $k=>$v)<option value="{{$k}}" @selected(request('role')===$k)>{{$v}}</option>@endforeach</select></div>
    <div class="col-lg-2"><label class="form-label fw-semibold">สถานะ</label><select name="status" class="form-select rounded-pill"><option value="">ทุกสถานะ</option><option value="active" @selected(request('status')==='active')>Active</option><option value="suspended" @selected(request('status')==='suspended')>Suspended</option></select></div>
    <div class="col-lg-2"><button class="btn btn-danger rounded-pill w-100">ค้นหา</button></div>
  </div>
</form>
<div class="card user-card">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light"><tr><th><input type="checkbox"></th><th>โปรไฟล์</th><th>อีเมล / เบอร์โทร</th><th>บทบาท</th><th>ใช้งานล่าสุด</th><th>สถานะ</th><th class="text-end">จัดการ</th></tr></thead>
      <tbody>
      @forelse($users as $u)
        <tr>
          <td><input type="checkbox" value="{{$u->id}}"></td>
          <td><div class="d-flex align-items-center gap-3"><div class="avatar-circle">{{ mb_substr($u->name,0,1) }}</div><div><div class="fw-bold">{{ $u->name }}</div><div class="text-muted small">User ID: #{{ $u->id }}</div></div></div></td>
          <td><div>{{ $u->email }}</div><div class="text-muted small">{{ $u->phone ?: '-' }}</div></td>
          <td><span class="role-badge role-{{ $u->role }}">{{ str_replace('_',' ', $u->role) }}</span></td>
          <td><span class="text-muted">{{ optional($u->updated_at)->format('d/m/Y H:i') }}</span></td>
          <td>@if($u->is_active)<span class="status-pill bg-success-subtle text-success">Active</span>@else<span class="status-pill bg-secondary-subtle text-secondary">Suspended</span>@endif</td>
          <td class="text-end"><div class="action-stack justify-content-end">
            <a class="btn btn-soft" href="{{ route('admin.users.show',$u) }}"><i class="bi bi-eye"></i> ดู</a>
            <a class="btn btn-soft" href="{{ route('admin.users.edit',$u) }}"><i class="bi bi-pencil"></i> แก้ไข</a>
            <form method="post" action="{{ route('admin.users.resetPassword',$u) }}" onsubmit="return confirm('รีเซ็ตรหัสผ่านผู้ใช้นี้เป็น password ?')">@csrf<button class="btn btn-outline-warning rounded-pill"><i class="bi bi-key"></i> รีเซ็ต</button></form>
            <form method="post" action="{{ route('admin.users.toggleStatus',$u) }}" onsubmit="return confirm('ยืนยันเปลี่ยนสถานะบัญชีนี้?')">@csrf @method('patch')<button class="btn btn-outline-secondary rounded-pill">{{ $u->is_active ? 'ระงับ' : 'เปิดใช้' }}</button></form>
          </div></td>
        </tr>
      @empty
        <tr><td colspan="7" class="text-center py-5 text-muted">ไม่พบผู้ใช้งาน</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  <div class="p-3 d-flex justify-content-between align-items-center"><div class="text-muted">ทั้งหมด {{ $users->total() }} รายการ</div>{{ $users->links('pagination::bootstrap-5') }}</div>
</div>
@endsection
