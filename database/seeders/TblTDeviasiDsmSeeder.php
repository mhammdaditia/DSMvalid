<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TblTDeviasiDsmSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing data
        DB::table('tbl_t_deviasi_dsm')->truncate();

        // Insert dummy data
        $events = [
            [
                'alarm_id' => 'ALM006',
                'no_unit' => 'HD7105',
                'alarm' => 'Tailgating',
                'tanggal' => '2025-10-23 12:00:00',
                'location' => 'Jl. Test Location',
                'speed' => 70.0,
                'IsVideoSent' => 0,
                'VideoFileName' => null,
            ],
             [
                'alarm_id' => 'ALM007',
                'no_unit' => 'HD7134',
                'alarm' => 'Yawning',
                'tanggal' => '2025-10-24 12:00:00',
                'location' => 'Jl. Test Location',
                'speed' => 70.0,
                'IsVideoSent' => 0,
                'VideoFileName' => null,
            ],
             [
                'alarm_id' => 'ALM076',
                'no_unit' => 'HD7205',
                'alarm' => 'Tailgating',
                'tanggal' => '2025-10-23 12:00:00',
                'location' => 'Jl. Test Location',
                'speed' => 70.0,
                'IsVideoSent' => 0,
                'VideoFileName' => null,
            ],
             [
                'alarm_id' => 'ALM107',
                'no_unit' => 'HD7234',
                'alarm' => 'Yawning',
                'tanggal' => '2025-10-24 12:00:00',
                'location' => 'Jl. Test Location',
                'speed' => 70.0,
                'IsVideoSent' => 0,
                'VideoFileName' => null,
            ],
        ];

        DB::table('tbl_t_deviasi_dsm')->insert($events);

        $this->command->info('✅ Successfully seeded 6 events to tbl_t_deviasi_dsm!');
    }
}
