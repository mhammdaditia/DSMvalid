<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event as EventFacade; // ← Pakai alias
use Illuminate\Support\Facades\Log;
use App\Models\Event;
use App\Observers\EventObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogUserLogin;
use App\Listeners\LogUserLogout;

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
        // Observer untuk model Event
        Event::observe(EventObserver::class);
        Log::info('✅ EventObserver registered in AppServiceProvider');
        
        // Event listener untuk login/logout
        EventFacade::listen(Login::class, LogUserLogin::class);
        EventFacade::listen(Logout::class, LogUserLogout::class);
    }
}