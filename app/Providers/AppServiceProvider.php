<?php

namespace App\Providers;

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
        if ($this->app->environment('production')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // A public demo may run with APP_ENV=production, but simulation must
        // never be enabled in a non-demo production application.
        if (app()->environment('production')
            && config('hoyos.allow_payment_simulation')
            && !config('hoyos.demo_mode')) {
            \Illuminate\Support\Facades\Log::critical('CRITICAL SECURITY HAZARD: Payment simulation is enabled in production environment.');
            throw new \RuntimeException('Security misconfiguration: Payment simulation cannot be enabled in production.');
        }
    }
}
