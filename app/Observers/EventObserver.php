<?php

namespace App\Observers;

use App\Models\Event;
use App\Models\User;
use App\Events\NewEventCreated;
use App\Notifications\NewEventNotification;
use Illuminate\Support\Facades\Log;

class EventObserver
{
    /**
     * Handle the Event "created" event.
     */
    public function created(Event $event): void
    {
        Log::info('🟢 EventObserver: New record detected', [
            'ID' => $event->id,
            'Alarm_id' => $event->alarm_id,
            'No_unit' => $event->no_unit
        ]);

        // 1. Broadcast real-time via Reverb (untuk toast) ✅ INI SUDAH JALAN
        broadcast(new NewEventCreated($event));
        Log::info('🔵 Broadcast sent via NewEventCreated');
        
        // 2. Send notification ke semua users (save ke database)
        try {
            $users = User::all();
            
            Log::info('📧 Sending notifications to users', [
                'users_count' => $users->count()
            ]);
            
            foreach ($users as $user) {
                $user->notify(new NewEventNotification($event));
            }
            
            Log::info('✅ Notifications sent successfully', [
                'users_notified' => $users->count()
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error sending notifications: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
