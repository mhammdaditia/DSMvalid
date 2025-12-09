<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Jalankan semua seeder
        $this->call([
            // AdminUserSeeder::class,
           EventSeeder::class,
        ]);
    }
}
