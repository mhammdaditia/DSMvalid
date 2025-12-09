<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LogPageActivity
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            try {
                // Get hostname from request
                $hostname = $this->getHostname($request);
                
                DB::table('activity_logs')->insert([
                    'user_id'    => auth()->id(),
                    'route'      => $request->route()?->getName(),
                    'url'        => $request->fullUrl(),
                    'method'     => $request->method(),
                    'action'     => 'page_visit',
                    'ip_address' => $request->ip(),
                    'hostname'   => $hostname, 
                    'created_at' => now(),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to log activity: ' . $e->getMessage());
            }
        }

        return $next($request);
    }

    /**
     * Get hostname from request
     */
    private function getHostname(Request $request): ?string
    {
        try {
            // Method 1: Dari HTTP header (jika browser support)
            $hostname = $request->header('X-Forwarded-Host') 
                ?? $request->header('Host');
            
            // Method 2: Reverse DNS lookup dari IP
            if (!$hostname || $hostname === $request->getHost()) {
                $ip = $request->ip();
                $hostname = gethostbyaddr($ip);
                
                // Jika reverse DNS gagal, return IP
                if ($hostname === $ip) {
                    $hostname = null;
                }
            }
            
            // Method 3: User Agent sebagai fallback
            if (!$hostname) {
                $userAgent = $request->userAgent();
                // Extract hostname dari user agent jika ada
                if (preg_match('/\(([^)]+)\)/', $userAgent, $matches)) {
                    $hostname = $matches[1];
                }
            }
            
            return $hostname;
        } catch (\Exception $e) {
            Log::error('Failed to get hostname: ' . $e->getMessage());
            return null;
        }
    }
}