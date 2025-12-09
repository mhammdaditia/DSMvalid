<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // ✅ Admin ATAU Super Admin bisa akses
        if (auth()->check() && (auth()->user()->role === 'admin' || auth()->user()->role === 'super_admin')) {
            return $next($request);
        }

        abort(403, 'Unauthorized. Admin access only.');
    }
}