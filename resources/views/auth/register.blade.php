@extends('layouts.app')
@section('content')
<div class="container" style="max-width:560px">
    <div class="bg-white rounded-4 shadow-sm p-4">
        <h2 class="fw-bold mb-3">{{__('Register')}}</h2>
        @if($errors->any())<div class="alert alert-danger rounded-4">{{$errors->first()}}</div>@endif
        <form method="post" class="mb-3">@csrf
            <input class="form-control rounded-pill mb-2" name="name" placeholder="{{__('Name')}}" required>
            <input class="form-control rounded-pill mb-2" name="email" type="email" placeholder="Email" required>
            <input class="form-control rounded-pill mb-2" name="phone" inputmode="numeric" pattern="[0-9]{9,10}" maxlength="10" placeholder="{{__('Phone')}}">
            <input class="form-control rounded-pill mb-2" name="password" type="password" placeholder="Password" required>
            <input class="form-control rounded-pill mb-2" name="password_confirmation" type="password" placeholder="Confirm Password" required>
            <button class="btn btn-danger rounded-pill w-100">{{__('Register')}}</button>
        </form>
        <div class="text-center text-muted my-3">{{__('or login with')}}</div>
        <div class="d-grid gap-2">
            <a class="btn btn-outline-danger rounded-pill" href="{{route('social.redirect','google')}}"><i class="bi bi-google me-2"></i> Google</a>
            <a class="btn btn-outline-primary rounded-pill" href="{{route('social.redirect','facebook')}}"><i class="bi bi-facebook me-2"></i> Facebook</a>
            <a class="btn btn-outline-success rounded-pill" href="{{route('social.redirect','line')}}"><i class="bi bi-line me-2"></i> LINE</a>
        </div>
        <p class="small text-muted mt-3 mb-0">ถ้าเข้าสู่ระบบด้วย Google / Facebook / LINE ครั้งแรก ระบบจะสร้างบัญชี Member ให้อัตโนมัติ</p>
    </div>
</div>
@endsection
