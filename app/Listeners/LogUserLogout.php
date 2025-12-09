<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\DB;

class LogUserLogout
{
    public function handle(Logout $event): void
    {
        try {
            $request = request();
            $hostname = $this->getHostname($request);
            
            DB::table('activity_logs')->insert([
                'user_id'    => $event->user->id,
                'route'      => 'logout',
                'url'        => $request->fullUrl(),
                'method'     => 'POST',
                'action'     => 'logout',
                'ip_address' => $request->ip(),
                'hostname'   => $hostname, // ← TAMBAHAN
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to log logout activity: ' . $e->getMessage());
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