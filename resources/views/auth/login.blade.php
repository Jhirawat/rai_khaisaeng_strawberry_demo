@extends('layouts.app')
@push('styles')<link rel="stylesheet" href="{{asset('css/auth-refresh.css')}}">@endpush
@section('content')
<div class="container auth-shell">
    <div class="auth-card">
        <section class="auth-form-panel">
            <a href="{{route('shop.home')}}" class="auth-back"><i class="bi bi-arrow-left"></i> {{__('Back to shop')}}</a>
            <div class="auth-heading"><span>{{__('Member account')}}</span><h1>{{__('Welcome back')}}</h1><p>{{__('Sign in to shop, track orders, and save your delivery details.')}}</p></div>
            @if($errors->any())<div class="alert alert-danger auth-alert" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{$errors->first()}}</span></div>@endif
            <form method="post" class="auth-form">@csrf
                <div class="form-field"><label for="loginEmail">{{__('Email')}}</label><div class="input-with-icon"><i class="bi bi-envelope"></i><input id="loginEmail" class="form-control" name="email" type="email" value="{{old('email')}}" autocomplete="email" placeholder="name@example.com" required autofocus></div></div>
                <div class="form-field"><label for="loginPassword">{{__('Password')}}</label><div class="input-with-icon"><i class="bi bi-lock"></i><input id="loginPassword" class="form-control" name="password" type="password" autocomplete="current-password" placeholder="••••••••" minlength="8" required><button class="password-toggle" type="button" data-password-toggle aria-label="{{__('Show password')}}"><i class="bi bi-eye"></i></button></div></div>
                <label class="remember-check"><input type="checkbox" name="remember" value="1"> <span>{{__('Keep me signed in')}}</span></label>
                <button class="btn btn-brand auth-submit" type="submit">{{__('Login')}} <i class="bi bi-arrow-right"></i></button>
            </form>
            <div class="auth-divider"><span>{{__('or continue with')}}</span></div>
            <div class="social-grid">
                @foreach($socialProviders as $provider => $meta)
                    @if($meta['ready'])
                        <a class="social-login social-{{$provider}}" href="{{route('social.redirect',$provider)}}"><i class="bi bi-{{$provider === 'line' ? 'line' : $provider}}"></i><span>{{$meta['label']}}</span></a>
                    @else
                        <button class="social-login social-{{$provider}} is-disabled" type="button" disabled title="{{__('Provider setup required')}}"><i class="bi bi-{{$provider === 'line' ? 'line' : $provider}}"></i><span>{{$meta['label']}}</span><small>{{__('Setup')}}</small></button>
                    @endif
                @endforeach
            </div>
            <p class="auth-switch">{{__('New to Rai Khaisaeng?')}} <a href="{{route('register')}}">{{__('Create an account')}}</a></p>
            <details class="demo-accounts"><summary>{{__('Demo accounts')}}</summary><div><span>Member</span><code>user_test@khaisaeng.test</code><code>password</code></div><div><span>Admin</span><code>admin_test@khaisaeng.test</code><code>password</code></div><div><span>Super Admin</span><code>sbadmin_test@khaisaeng.test</code><code>password</code></div></details>
        </section>
        <aside class="auth-story" aria-label="Rai Khaisaeng Strawberry Farm"><div class="auth-story-overlay"></div><div class="auth-story-content"><span class="story-pill"><i class="bi bi-geo-alt"></i> {{__('Samoeng, Chiang Mai')}}</span><blockquote>“{{__('From our mountain farm to your table, every order supports a local growing community.')}}”</blockquote><div class="story-proof"><strong>20+</strong><span>{{__('Years of farming experience')}}</span></div></div></aside>
    </div>
</div>
@endsection
@push('scripts')
<script>document.querySelectorAll('[data-password-toggle]').forEach(function(button){button.addEventListener('click',function(){const input=button.parentElement.querySelector('input');const showing=input.type==='text';input.type=showing?'password':'text';button.innerHTML='<i class="bi bi-'+(showing?'eye':'eye-slash')+'"></i>';button.setAttribute('aria-label',showing?'{{__('Show password')}}':'{{__('Hide password')}}');});});</script>
@endpush
