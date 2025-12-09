<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Events\NewEventCreated;
use Illuminate\Database\Seeder;

class TestReverbSeeder extends Seeder
{
    /**
     * Run the database seeds untuk test Reverb real-time notification.
     */
    public function run(): void
    {
        $this->command->info('🚀 Testing Laravel Reverb Broadcasting...');
        $this->command->info('📡 Make sure you have:');
        $this->command->info('   1. php artisan reverb:start (running in another terminal)');
        $this->command->info('   2. npm run dev (running in another terminal)');
        $this->command->info('   3. Your browser opened at your app URL');
        $this->command->newLine();
        
        $this->command->info('⏳ Broadcasting 5 test events to channel: dsm-events');
        $this->command->newLine();

        // Create 5 test events dengan delay
        for ($i = 1; $i <= 5; $i++) {
            $event = Event::create([
                'alarm_id' => 'TEST-' . now()->format('YmdHis') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'no_unit' => 'B' . rand(1000, 9999) . 'XX',
                'alarm' => ['Overspeed', 'Harsh Braking', 'Harsh Acceleration'][rand(0, 2)],
                'tanggal' => now()->subMinutes(rand(1, 30)),
                'speed' => rand(80, 120),
                'location' => 'Jl. Test Location ' . $i . ', Jakarta Selatan',
                'IsVideoSent' => (bool)rand(0, 1),
                'VideoFileName' => rand(0, 1) ? 'test_video_' . $i . '.mp4' : null,
            ]);

            // Broadcast menggunakan NewEventCreated
            broadcast(new NewEventCreated($event));

            $this->command->info("✅ Event #{$i} broadcasted:");
            $this->command->info("   - Unit: {$event->no_unit}");
            $this->command->info("   - Alarm: {$event->alarm}");
            $this->command->info("   - Speed: {$event->speed} km/h");
            $this->command->newLine();
            
            // Wait 3 seconds before next broadcast
            sleep(3);
        }

        $this->command->info('🎉 All test events broadcasted!');
        $this->command->info('👀 Check your browser for:');
        $this->command->info('   ✓ Toast notifications (top right)');
        $this->command->info('   ✓ Table auto-refresh');
        $this->command->info('   ✓ Click toast to open validation modal');
    }
}
