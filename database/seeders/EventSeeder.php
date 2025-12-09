<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use Carbon\Carbon;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        echo "🌱 Seeding 3 events...\n\n";

        // Event 1 - Drowsiness
        Event::create([
            'alarm_id' => 'DSM-' . Carbon::now()->subHours(2)->timestamp . '-0001',
            'no_unit' => 'HD785-01',
            'alarm' => 'Drowsiness Detected',
            'tanggal' => Carbon::now()->subHours(2),
            'location' => 'Hauling Road KM 10',
            'speed' => 45.5,
            'IsVideoSent' => false,
            'VideoFileName' => null,
        ]);
        echo "  ✓ Event #1: HD785-01 - Drowsiness Detected\n";

        // Event 2 - Smoking
        Event::create([
            'alarm_id' => 'DSM-' . Carbon::now()->subHours(1)->timestamp . '-0002',
            'no_unit' => 'HD265-02',
            'alarm' => 'Smoking Detected',
            'tanggal' => Carbon::now()->subHours(1),
            'location' => 'Pit Area 1 - Section A',
            'speed' => 32.8,
            'IsVideoSent' => false,
            'VideoFileName' => null,
        ]);
        echo "  ✓ Event #2: HD465-02 - Smoking Detected\n";

        // Event 3 - Phone Usage
        Event::create([
            'alarm_id' => 'DSM-1' . Carbon::now()->subMinutes(30)->timestamp . '-0003',
            'no_unit' => 'PC11000-03',
            'alarm' => 'Phone Usage Detected',
            'tanggal' => Carbon::now()->subMinutes(30),
            'location' => 'Loading Point Alpha',
            'speed' => 28.3,
            'IsVideoSent' => false,
            'VideoFileName' => null,
        ]);
        echo "  ✓ Event #3: PC2000-03 - Phone Usage Detected\n";

        echo "\n✅ 3 events created successfully!\n";
    }
}
