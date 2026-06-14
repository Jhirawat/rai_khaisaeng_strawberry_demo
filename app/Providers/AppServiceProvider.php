<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // LINE Login ต้องติดตั้ง socialiteproviders/line ก่อน จึงจะ extend driver ได้
        if (class_exists(\SocialiteProviders\Manager\SocialiteWasCalled::class)
            && class_exists(\SocialiteProviders\Line\Provider::class)) {
            Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
                $event->extendSocialite('line', \SocialiteProviders\Line\Provider::class);
            });
        }
    }
}
