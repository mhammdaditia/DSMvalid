<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

class LogUserLogin
{
    public function handle(Login $event): void
    {
        try {
            $request = request();
            $hostname = $this->getHostname($request);
            
            DB::table('activity_logs')->insert([
                'user_id'    => $event->user->id,
                'route'      => 'login',
                'url'        => $request->fullUrl(),
                'method'     => 'POST',
                'action'     => 'login',
                'ip_address' => $request->ip(),
                'hostname'   => $hostname, // ← TAMBAHAN
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log login activity: ' . $e->getMessage());
        }
    }

    private function getHostname($request): ?string
    {
        try {
            $ip = $request->ip();
            $hostname = gethostbyaddr($ip);
            
            return $hostname !== $ip ? $hostname : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}