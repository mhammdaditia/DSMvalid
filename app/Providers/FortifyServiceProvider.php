<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Authentication menggunakan USERNAME
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('username', $request->username)->first();

            if ($user && Hash::check($request->password, $user->password)) {
                return $user;
            }

            return null;
        });

        // Login view
        Fortify::loginView(function () {
            return inertia('Auth/Login');
        });

        // ❌ HAPUS registerView - tidak ada halaman register publik

        // Rate limiting
        RateLimiter::for('login', function (Request $request) {
            $username = (string) $request->username;
            return Limit::perMinute(5)->by($username . $request->ip());
        });
    }
}
