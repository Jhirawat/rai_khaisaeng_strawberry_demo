@extends('layouts.admin')
@section('title','Backup / Export')
@section('content')
<div class="row g-3">
@php($items=[['สินค้า','products','bi-box-seam','Export รายการสินค้า ราคา สถานะ และสต็อก'],['ออเดอร์','orders','bi-receipt','Export รายการคำสั่งซื้อและยอดขาย'],['สมาชิก','users','bi-people','Export ข้อมูลสมาชิกและสิทธิ์'],['คลังสินค้า','inventory','bi-archive','Export จำนวนคงเหลือและจุดเตือนใกล้หมด']])
@foreach($items as [$title,$route,$icon,$desc])
<div class="col-md-6 col-xl-3"><div class="content-card p-4 h-100"><i class="bi {{$icon}} fs-1 text-danger"></i><h5 class="fw-bold mt-2">{{$title}}</h5><p class="text-muted small">{{$desc}}</p><a class="btn btn-admin rounded-pill w-100" href="{{route('admin.exports.'.$route)}}"><i class="bi bi-download"></i> ดาวน์โหลด CSV</a></div></div>
@endforeach
</div>
<div class="alert alert-warning rounded-4 mt-4"><strong>แนะนำ:</strong> ก่อน migrate/seed บน Production ให้ Export ข้อมูลสำคัญไว้ทุกครั้ง เพื่อลดความเสี่ยงข้อมูลหาย</div>
@endsection
