<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Yandex\Provider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Auth::guard('web')->setRememberDuration((int) config('auth.remember_minutes'));

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('yandex', Provider::class);
        });
    }
}
