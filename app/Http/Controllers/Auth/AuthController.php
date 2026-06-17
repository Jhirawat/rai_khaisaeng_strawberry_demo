<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use App\Support\ActivityLogger;

class AuthController extends Controller
{
    public function loginForm(){return view('auth.login');}
    public function registerForm(){return view('auth.register');}

    public function login(Request $r)
    {
        $data=$r->validate(['email'=>'required|email|max:255','password'=>'required|string|min:8|max:255']);
        $key = 'login:'.strtolower($data['email']).'|'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages(['email' => 'พยายามเข้าสู่ระบบหลายครั้งเกินไป กรุณารอ '.$seconds.' วินาที']);
        }
        if(Auth::attempt($data,$r->boolean('remember'))){
            RateLimiter::clear($key);
            $r->session()->regenerate();
            ActivityLogger::log('auth.login', auth()->user(), ['role'=>auth()->user()->role], auth()->user()->email);
            return auth()->user()->role==='member'?redirect()->route('shop.home'):redirect()->route('admin.dashboard');
        }
        RateLimiter::hit($key, 60);
        return back()->withErrors(['email'=>'อีเมลหรือรหัสผ่านไม่ถูกต้อง']);
    }

    public function register(Request $r)
    {
        $d=$r->validate(['name'=>'required|string|max:255','email'=>'required|email|max:255|unique:users,email','password'=>'required|string|min:8|confirmed','phone'=>['nullable','regex:/^[0-9]{9,10}$/']]);
        $u=User::create(['name'=>$d['name'],'email'=>$d['email'],'password'=>Hash::make($d['password']),'phone'=>$d['phone']??null,'role'=>'member']);
        Auth::login($u);
        ActivityLogger::log('auth.register', $u, ['role'=>'member'], $u->email);
        return redirect()->route('shop.home');
    }

    private function socialProviderIsConfigured(string $provider): bool
    {
        if (! in_array($provider, ['google', 'facebook', 'line'], true)) {
            return false;
        }

        return filled(config("services.$provider.client_id"))
            && filled(config("services.$provider.client_secret"))
            && filled(config("services.$provider.redirect"));
    }

    private function socialProviderError(string $provider): ?string
    {
        if (! in_array($provider, ['google', 'facebook', 'line'], true)) {
            return 'ไม่พบผู้ให้บริการ Social Login นี้';
        }

        if ($provider === 'line' && ! class_exists(\SocialiteProviders\Line\Provider::class)) {
            return 'LINE Login ยังไม่ได้ติดตั้ง Provider ให้รัน composer require socialiteproviders/line ก่อน';
        }

        if (! $this->socialProviderIsConfigured($provider)) {
            return strtoupper($provider).' Login ยังไม่ได้ตั้งค่า Client ID / Secret / Redirect URI ใน Railway Variables';
        }

        return null;
    }

    public function redirectToProvider(string $provider)
    {
        abort_unless(in_array($provider,['google','facebook','line'], true),404);

        if ($message = $this->socialProviderError($provider)) {
            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        try {
            return Socialite::driver($provider)->redirect();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['email'=>'เชื่อมต่อ '.$provider.' ไม่สำเร็จ: '.$e->getMessage()]);
        }
    }

    public function handleProviderCallback(string $provider)
    {
        abort_unless(in_array($provider,['google','facebook','line'], true),404);

        if ($message = $this->socialProviderError($provider)) {
            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['email'=>'เชื่อมต่อ '.$provider.' ไม่สำเร็จ กรุณาตรวจสอบ Client ID/Secret/Callback URL ใน Railway Variables']);
        }
        $email = $socialUser->getEmail() ?: ($provider.'_'.$socialUser->getId().'@maeyangha.social');
        $user = User::where('provider',$provider)->where('provider_id',$socialUser->getId())->first()
            ?: User::where('email',$email)->first();
        if(!$user){
            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: ucfirst($provider).' User',
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'role' => 'member',
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar' => $socialUser->getAvatar(),
            ]);
        } else {
            $user->update(['provider'=>$provider,'provider_id'=>$socialUser->getId(),'avatar'=>$socialUser->getAvatar()]);
        }
        Auth::login($user, true);
        ActivityLogger::log('auth.social_login', $user, ['provider'=>$provider], $user->email);
        return redirect()->route('shop.home');
    }

    public function logout(Request $r)
    {
        $user = auth()->user();
        if ($user) ActivityLogger::log('auth.logout', $user, ['role'=>$user->role], $user->email);
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('shop.home');
    }
}
