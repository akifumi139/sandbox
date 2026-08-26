<?php

namespace Modules\Syako\Database\Seeders;

use Illuminate\Database\Seeder;

class SyakoDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(VehicleSeeder::class);
    }
}
