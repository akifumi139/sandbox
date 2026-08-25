<?php

namespace Modules\Heya\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Heya\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([
            ['name' => '会議室 A'],
            ['name' => '会議室 B'],
            ['name' => '会議室 C'],
        ] as $room) {
            Room::query()->updateOrCreate(['name' => $room['name']], $room + ['is_active' => true]);
        }
    }
}
