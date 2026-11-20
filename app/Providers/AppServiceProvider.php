<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Yandex\Provider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Independent session budgets prevent unrelated visitors behind the
        // same reverse proxy/NAT from exhausting the registration allowance.
        // The wider IP cap still restricts session rotation by automated clients.
        RateLimiter::for('registration', static fn (Request $request): array => [
            Limit::perMinute(8)->by('session:'.$request->session()->getId()),
            Limit::perMinute(40)->by('ip:'.$request->ip()),
        ]);

        Auth::guard('web')->setRememberDuration((int) config('auth.remember_minutes'));

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('yandex', Provider::class);
        });
    }
}
