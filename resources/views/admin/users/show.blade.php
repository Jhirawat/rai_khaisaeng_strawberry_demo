@extends('layouts.admin')
@section('title','รายละเอียดผู้ใช้งาน')
@section('content')
<style>.detail-card{border:0;border-radius:22px;box-shadow:0 10px 30px rgba(20,30,60,.07)}.avatar-xl{width:92px;height:92px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:#ffe5e9;color:#dc3545;font-size:2.2rem;font-weight:900}.role-badge{border-radius:999px;padding:.45rem .8rem;font-weight:800}.role-super_admin{background:#dc3545;color:#fff}.role-admin{background:#ff6b6b;color:#fff}.role-staff{background:#0d6efd;color:#fff}.role-member{background:#198754;color:#fff}</style>
<div class="row g-4">
  <div class="col-lg-8">
    <div class="card detail-card p-4 mb-4">
      <div class="d-flex align-items-center gap-3 mb-3"><div class="avatar-xl">{{ mb_substr($user->name,0,1) }}</div><div><h3 class="fw-bold mb-1">{{ $user->name }}</h3><div class="text-muted">User ID: #{{ $user->id }} · {{ $user->email }}</div></div></div>
      <div class="row g-3 mt-2"><div class="col-md-6"><div class="text-muted">เบอร์โทร</div><div class="fw-bold">{{ $user->phone ?: '-' }}</div></div><div class="col-md-6"><div class="text-muted">สถานะ</div><div>{!! $user->is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Suspended</span>' !!}</div></div><div class="col-12"><div class="text-muted">ที่อยู่</div><div class="fw-bold">{{ $user->address ?: '-' }}</div></div></div>
    </div>
    <div class="card detail-card p-4 mb-4">
      <h4 class="fw-bold mb-3">ประวัติการซื้อ</h4>
      <div class="table-responsive"><table class="table"><tr><th>เลขออเดอร์</th><th>ยอด</th><th>สถานะ</th><th>วันที่</th></tr>@forelse($user->orders()->latest()->limit(10)->get() as $order)<tr><td><a href="{{ route('admin.orders.show',$order) }}">{{ $order->order_number }}</a></td><td>฿{{ number_format($order->total,2) }}</td><td>{{ $order->status }}</td><td>{{ $order->created_at->format('d/m/Y H:i') }}</td></tr>@empty<tr><td colspan="4" class="text-muted text-center py-4">ยังไม่มีคำสั่งซื้อ</td></tr>@endforelse</table></div>
    </div>
    <div class="card detail-card p-4">
      <h4 class="fw-bold mb-3">Activity Logs</h4>
      <div class="table-responsive"><table class="table"><tr><th>กิจกรรม</th><th>เวลา</th><th>หมายเหตุ</th></tr><tr><td>อัปเดตบัญชีล่าสุด</td><td>{{ optional($user->updated_at)->format('d/m/Y H:i') }}</td><td>ข้อมูลพื้นฐานสำหรับตรวจสอบ</td></tr><tr><td>สมัครสมาชิก</td><td>{{ optional($user->created_at)->format('d/m/Y H:i') }}</td><td>-</td></tr></table></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card detail-card p-4 mb-4">
      <h4 class="fw-bold">Role & Security</h4>
      <p><span class="role-badge role-{{ $user->role }}">{{ str_replace('_',' ', $user->role) }}</span></p>
      <div class="d-grid gap-2">
        <a href="{{ route('admin.users.edit',$user) }}" class="btn btn-danger rounded-pill">แก้ไขผู้ใช้งาน</a>
        <form method="post" action="{{ route('admin.users.resetPassword',$user) }}" onsubmit="return confirm('สร้างรหัสผ่านชั่วคราวแบบสุ่มให้ผู้ใช้นี้?')">@csrf<button class="btn btn-outline-warning rounded-pill w-100">สร้างรหัสผ่านชั่วคราว</button></form>
        <form method="post" action="{{ route('admin.users.toggleStatus',$user) }}" onsubmit="return confirm('ยืนยันเปลี่ยนสถานะบัญชีนี้?')">@csrf @method('patch')<button class="btn btn-outline-secondary rounded-pill w-100">{{ $user->is_active ? 'ระงับบัญชี' : 'เปิดใช้งานบัญชี' }}</button></form>
        <form method="post" action="{{ route('admin.users.destroy',$user) }}" onsubmit="return confirm('ยืนยันลบบัญชีถาวร? การกระทำนี้ย้อนกลับไม่ได้')">@csrf @method('delete')<button class="btn btn-outline-danger rounded-pill w-100">ลบบัญชีถาวร</button></form>
      </div>
      <hr><div class="small text-muted">2FA Status: ยังไม่ได้เปิดใช้งาน<br>Password Reset: ระบบจะสุ่มรหัสที่เดายากและแสดงให้ผู้ดูแลคัดลอกเพียงครั้งเดียว</div>
    </div>
    <div class="card detail-card p-4">
      <h4 class="fw-bold">ที่อยู่จัดส่ง</h4>
      @forelse($user->addresses as $address)<div class="border rounded-4 p-3 mb-2"><div class="fw-bold">{{ $address->recipient_name }}</div><div class="text-muted small">{{ $address->phone }}</div><div>{{ $address->address }}</div></div>@empty<div class="text-muted">ยังไม่มีที่อยู่จัดส่ง</div>@endforelse
    </div>
  </div>
</div>
@endsection
