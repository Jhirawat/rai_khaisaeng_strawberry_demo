@extends('layouts.admin')
@section('title','โฆษณา / โปรโมชั่น')
@section('content')
@php
    $homePromo = $promotions->firstWhere('position','home_banner') ?: \App\Models\Promotion::where('position','home_banner')->orderBy('sort_order')->latest()->first();
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
    <div>
        <h4 class="fw-bold mb-1">จัดการโฆษณา / โปรโมชั่น</h4>
        <p class="text-muted mb-0">แก้รูปข้างโปรโมชั่น ข้อความ ปุ่ม และลิงก์ที่แสดงหน้าแรก</p>
    </div>
    <a class="btn btn-admin rounded-pill px-4" href="{{route('admin.promotions.create')}}"><i class="bi bi-plus-circle me-1"></i> เพิ่มโปรโมชั่น</a>
</div>

<div class="content-card p-4 mb-4">
    <div class="row g-4 align-items-center">
        <div class="col-lg-5">
            @if($homePromo?->image_url)
                <img src="{{$homePromo->image_url}}" class="img-fluid rounded-4 shadow-sm" style="width:100%;height:230px;object-fit:cover" alt="รูปข้างโปรโมชั่นหน้าแรก">
            @else
                <div class="rounded-4 d-flex align-items-center justify-content-center bg-light border" style="height:230px">
                    <div class="text-center text-muted"><i class="bi bi-image fs-1 d-block mb-2"></i>ยังไม่มีรูปข้างโปรโมชั่น</div>
                </div>
            @endif
        </div>
        <div class="col-lg-7">
            <span class="badge text-bg-danger rounded-pill px-3 py-2 mb-2">หน้าแรก: รูปข้างโปรโมชั่น</span>
            <h4 class="fw-bold mb-2">{{$homePromo->title ?? 'ยังไม่ได้ตั้งค่าโปรโมชั่นหน้าแรก'}}</h4>
            <p class="text-muted mb-2">{{$homePromo->description ?? 'เพิ่มหรือแก้ไขรายการตำแหน่ง home_banner เพื่อเปลี่ยนรูปด้านซ้าย ข้อความ ปุ่ม และลิงก์โปรโมชั่นหน้าแรก'}}</p>
            <div class="d-flex flex-wrap gap-2 mt-3">
                @if($homePromo)
                    <a class="btn btn-admin rounded-pill px-4" href="{{route('admin.promotions.edit',$homePromo)}}"><i class="bi bi-pencil-square"></i> แก้ไขรูปข้างโปรโมชั่น</a>
                @endif
                <a class="btn btn-outline-danger rounded-pill px-4" href="{{route('admin.promotions.create')}}"><i class="bi bi-plus-circle"></i> เพิ่มโปรโมชั่นหน้าแรกใหม่</a>
                <a class="btn btn-outline-secondary rounded-pill px-4" href="{{route('shop.home')}}" target="_blank"><i class="bi bi-eye"></i> ดูหน้าร้าน</a>
            </div>
            <div class="small text-muted mt-3">หมายเหตุ: เลือกตำแหน่ง <strong>หน้าแรก: รูปข้างโปรโมชั่น</strong> เพื่อให้แสดงในแถบโปรโมชั่นสีชมพูใต้ Hero Banner</div>
        </div>
    </div>
</div>

<div class="content-card p-3">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead class="table-light"><tr><th>รูป</th><th>หัวข้อ</th><th>ตำแหน่ง</th><th>ลำดับ</th><th>สถานะ</th><th class="text-end">จัดการ</th></tr></thead>
            <tbody>
            @forelse($promotions as $p)
                <tr>
                    <td style="width:140px">@if($p->image_url)<img src="{{$p->image_url}}" class="rounded-3" style="width:120px;height:70px;object-fit:cover">@else<span class="text-muted">ไม่มีรูป</span>@endif</td>
                    <td><div class="fw-bold">{{$p->title}}</div><div class="small text-muted">{{$p->subtitle}}</div></td>
                    <td><span class="badge text-bg-light">{{$p->position === 'home_banner' ? 'หน้าแรก: รูปข้างโปรโมชั่น' : $p->position}}</span></td>
                    <td>{{$p->sort_order}}</td>
                    <td>@if($p->is_active)<span class="badge text-bg-success">เปิดใช้งาน</span>@else<span class="badge text-bg-secondary">ปิด</span>@endif</td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary rounded-pill" href="{{route('admin.promotions.edit',$p)}}">แก้ไข</a><form method="post" action="{{route('admin.promotions.destroy',$p)}}" class="d-inline" onsubmit="return confirm('ลบโปรโมชั่นนี้?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger rounded-pill">ลบ</button></form></td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-5">ยังไม่มีโปรโมชั่น</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{$promotions->links()}}
</div>
@endsection
