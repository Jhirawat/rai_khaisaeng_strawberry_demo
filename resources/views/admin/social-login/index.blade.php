@extends('layouts.admin')
@section('title','Social Login Status')
@section('content')
<div class="content-card p-4">
    <h5 class="fw-bold">ตรวจสอบ Google / Facebook / LINE Login</h5>
    <p class="text-muted">ตั้งค่าใน Railway Variables ให้ครบ แล้วใช้ Callback URL ด้านล่างไปใส่ในผู้ให้บริการแต่ละเจ้า</p>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Provider</th><th>Client ID</th><th>Secret</th><th>Redirect ในระบบ</th><th>Callback URL ที่ต้องใช้</th><th>สถานะ</th></tr></thead><tbody>
    @foreach($providers as $p)<tr><td class="fw-bold">{{$p['label']}}</td><td>{!! $p['client_id'] ? '<span class="badge bg-success">มีแล้ว</span>' : '<span class="badge bg-danger">ยังไม่มี</span>' !!}</td><td>{!! $p['client_secret'] ? '<span class="badge bg-success">มีแล้ว</span>' : '<span class="badge bg-danger">ยังไม่มี</span>' !!}</td><td class="small">{{$p['redirect'] ?: '-'}}</td><td><code>{{$p['callback']}}</code></td><td>{!! $p['ready'] ? '<span class="badge bg-success">พร้อมใช้งาน</span>' : '<span class="badge bg-warning text-dark">ยังไม่ครบ</span>' !!}</td></tr>@endforeach
    </tbody></table></div>
    <div class="alert alert-info rounded-4 mb-0">Railway Variables ที่ต้องมี: <code>GOOGLE_CLIENT_ID</code>, <code>GOOGLE_CLIENT_SECRET</code>, <code>FACEBOOK_CLIENT_ID</code>, <code>FACEBOOK_CLIENT_SECRET</code>, <code>LINE_CLIENT_ID</code>, <code>LINE_CLIENT_SECRET</code></div>
</div>
@endsection
