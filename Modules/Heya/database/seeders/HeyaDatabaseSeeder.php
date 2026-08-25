<?php

namespace Modules\Heya\Database\Seeders;

use Illuminate\Database\Seeder;

class HeyaDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(RoomSeeder::class);
    }
}
