<?php

namespace Modules\Syako\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Syako\Models\Vehicle;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => '車両 A', 'vehicle_number' => '品川 500 あ 1001'],
            ['name' => '車両 B', 'vehicle_number' => '品川 500 あ 1002'],
            ['name' => '車両 C', 'vehicle_number' => '品川 500 あ 1003'],
        ] as $vehicle) {
            Vehicle::query()->updateOrCreate(['name' => $vehicle['name']], $vehicle + ['is_active' => true]);
        }
    }
}
