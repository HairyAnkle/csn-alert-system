<?php

namespace App\Providers;

use App\Models\Emergency;
use App\Observers\EmergencyObserver;
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
    }
}
