<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

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
        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
        
        Inertia::share([
            'auth' => [
                'user' => fn () => Auth::user(),
            ],
            'csrf_token' => fn () => csrf_token(),
        ]);
    }
}
