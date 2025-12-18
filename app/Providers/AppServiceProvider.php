<?php

namespace App\Providers;

use Illuminate\pagination\paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- SAYA TAMBAHKAN INI

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Paginator::useBootstrap();

        // <-- SAYA TAMBAHKAN INI: Paksa HTTPS saat di Railway (Production)
        if(config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}