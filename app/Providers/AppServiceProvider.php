<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Railway / Proxy deploy: บังคับ URL ที่ Laravel สร้างให้เป็น https เพื่อไม่ให้ Chrome ขึ้น Send anyway
        if (app()->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        if (class_exists(\SocialiteProviders\Manager\SocialiteWasCalled::class)
            && class_exists(\SocialiteProviders\Line\Provider::class)) {
            Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
                $event->extendSocialite('line', \SocialiteProviders\Line\Provider::class);
            });
        }
    }
}
