<?php

namespace App\Console\Commands;

use App\Events\NewEventCreated;
use App\Models\Event;
use App\Models\User;
use App\Notifications\NewEventNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CheckNewEvents extends Command
{
    protected $signature = 'events:check-new';
    protected $description = 'Check for new events from external database and send real-time notifications';

    public function handle()
    {
        try {
            // Get last processed event ID from cache
            $lastProcessedId = Cache::get('last_processed_event_id', 0);

            $this->info("Checking for new events... (Last processed ID: {$lastProcessedId})");

            // ✅ FIX: Only process events from last 3 days
            $cutoffDate = now()->subDays(3);

            // ✅ FIX: Use KAPITAL field names + Date filter
            $newEvents = Event::where('ID', '>', $lastProcessedId)
                ->where('Tanggal', '>=', $cutoffDate)                     // ✅ 3 hari terakhir
                ->whereNotIn('Alarm_id', function($query) {
                    $query->select('alarm_id')
                          ->from('validations');
                })
                ->orderBy('ID', 'asc')
                ->limit(10)
                ->get();

            if ($newEvents->count() > 0) {
                $this->info("Found {$newEvents->count()} new event(s)!");

                foreach ($newEvents as $event) {
                    // 1. Broadcast real-time notification via Reverb
                    broadcast(new NewEventCreated($event));
                    
                    $this->line("✓ Broadcast sent: Event #{$event->ID} - Unit {$event->No_Unit}");

                    // 2. Save notification ke database untuk semua users
                    $users = User::all();
                    foreach ($users as $user) {
                        $user->notify(new NewEventNotification($event));
                    }

                    $this->line("✓ Database notification saved for {$users->count()} user(s)");

                    // Log activity
                    Log::info("New event notification sent", [
                        'event_id' => $event->ID,
                        'alarm_id' => $event->Alarm_id,
                        'no_unit' => $event->No_Unit,
                        'tanggal' => $event->Tanggal,
                    ]);
                }

                Cache::forever('last_processed_event_id', $newEvents->last()->ID);
                $this->info("✓ Updated last processed ID to: {$newEvents->last()->ID}");
            } else {
                $this->info("No new events found (checked events from {$cutoffDate->format('Y-m-d H:i:s')}).");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error("CheckNewEvents Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}