@extends('layouts.admin')
@section('title','Activity Log')
@section('content')
<div class="content-card p-4">
    <div class="d-flex flex-wrap justify-content-between gap-2 align-items-center mb-3">
        <div><h5 class="fw-bold mb-1">ประวัติการทำงานของแอดมิน</h5><div class="text-muted small">ตรวจสอบย้อนหลังว่าใครเพิ่ม/แก้ไข/ลบข้อมูลสำคัญเมื่อไหร่</div></div>
        <form class="d-flex gap-2" method="get"><input class="form-control rounded-pill" name="search" value="{{request('search')}}" placeholder="ค้นหา"><select class="form-select rounded-pill" name="action"><option value="">ทุก Action</option>@foreach($actions as $a)<option value="{{$a}}" @selected(request('action')===$a)>{{$a}}</option>@endforeach</select><button class="btn btn-admin rounded-pill px-4">ค้นหา</button></form>
    </div>
    <div class="table-responsive"><table class="table align-middle"><thead><tr><th>เวลา</th><th>ผู้ใช้</th><th>Action</th><th>รายการ</th><th>IP</th><th>รายละเอียด</th></tr></thead><tbody>
    @forelse($logs as $log)<tr><td class="small text-muted">{{$log->created_at->format('d/m/Y H:i')}}</td><td>{{$log->user->name ?? 'System'}}</td><td><span class="badge badge-admin">{{$log->action}}</span></td><td>{{$log->subject_label ?? '-'}}</td><td class="small">{{$log->ip_address}}</td><td class="small"><code>{{ Str::limit(json_encode($log->properties, JSON_UNESCAPED_UNICODE), 120) }}</code></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-4">ยังไม่มีประวัติ</td></tr>@endforelse
    </tbody></table></div>{{$logs->links()}}
</div>
@endsection
