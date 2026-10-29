<?php

namespace App\Providers;

use App\Models\Emergency;
use App\Observers\EmergencyObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Emergency::observe(EmergencyObserver::class);

        if (request()->header('x-forwarded-proto') === 'https'
            || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
            || str_contains(request()->header('host', ''), 'trycloudflare.com')
            || str_contains(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
