@extends('layouts.app')
@push('styles')<link rel="stylesheet" href="{{asset('css/auth-refresh.css')}}">@endpush
@section('content')
<div class="container auth-shell">
    <div class="auth-card register-card">
        <aside class="auth-story" aria-label="Rai Khaisaeng Strawberry Farm"><div class="auth-story-overlay"></div><div class="auth-story-content"><span class="story-pill"><i class="bi bi-basket2"></i> {{__('Member benefits')}}</span><blockquote>“{{__('Shop local, follow every order, and make your next harvest easier to enjoy.')}}”</blockquote><div class="story-proof"><strong>72</strong><span>{{__('Farm and community products')}}</span></div></div></aside>
        <section class="auth-form-panel">
            <a href="{{route('shop.home')}}" class="auth-back"><i class="bi bi-arrow-left"></i> {{__('Back to shop')}}</a>
            <div class="auth-heading"><span>{{__('Member account')}}</span><h1>{{__('Create your account')}}</h1><p>{{__('Register once to order products, save delivery details, and check purchase history.')}}</p></div>
            @if($errors->any())<div class="alert alert-danger auth-alert" role="alert"><i class="bi bi-exclamation-circle"></i><span>{{$errors->first()}}</span></div>@endif
            <form method="post" class="auth-form">@csrf
                <div class="register-grid">
                    <div class="form-field full"><label for="registerName">{{__('Name')}}</label><div class="input-with-icon"><i class="bi bi-person"></i><input id="registerName" class="form-control" name="name" value="{{old('name')}}" autocomplete="name" required></div></div>
                    <div class="form-field"><label for="registerEmail">{{__('Email')}}</label><div class="input-with-icon"><i class="bi bi-envelope"></i><input id="registerEmail" class="form-control" name="email" type="email" value="{{old('email')}}" autocomplete="email" placeholder="name@example.com" required></div></div>
                    <div class="form-field"><label for="registerPhone">{{__('Phone')}}</label><div class="input-with-icon"><i class="bi bi-telephone"></i><input id="registerPhone" class="form-control" name="phone" value="{{old('phone')}}" inputmode="numeric" pattern="[0-9]{9,10}" maxlength="10" autocomplete="tel"></div></div>
                    <div class="form-field"><label for="registerPassword">{{__('Password')}}</label><div class="input-with-icon"><i class="bi bi-lock"></i><input id="registerPassword" class="form-control" name="password" type="password" autocomplete="new-password" minlength="8" required></div><p class="field-hint">{{__('Use at least 8 characters')}}</p></div>
                    <div class="form-field"><label for="registerConfirm">{{__('Confirm Password')}}</label><div class="input-with-icon"><i class="bi bi-shield-check"></i><input id="registerConfirm" class="form-control" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required></div></div>
                </div>
                <button class="btn btn-brand auth-submit" type="submit">{{__('Create an account')}} <i class="bi bi-arrow-right"></i></button>
            </form>
            <div class="auth-divider"><span>{{__('or continue with')}}</span></div>
            <div class="social-grid">
                @foreach($socialProviders as $provider => $meta)
                    @if($meta['ready'])<a class="social-login social-{{$provider}}" href="{{route('social.redirect',$provider)}}"><i class="bi bi-{{$provider === 'line' ? 'line' : $provider}}"></i><span>{{$meta['label']}}</span></a>
                    @else<button class="social-login social-{{$provider}} is-disabled" type="button" disabled title="{{__('Provider setup required')}}"><i class="bi bi-{{$provider === 'line' ? 'line' : $provider}}"></i><span>{{$meta['label']}}</span><small>{{__('Setup')}}</small></button>@endif
                @endforeach
            </div>
            <p class="auth-switch">{{__('Already a member?')}} <a href="{{route('login')}}">{{__('Login')}}</a></p>
        </section>
    </div>
</div>
@endsection
