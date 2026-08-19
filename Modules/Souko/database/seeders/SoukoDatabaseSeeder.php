<?php

namespace Modules\Souko\Database\Seeders;

use Illuminate\Database\Seeder;

class SoukoDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ToolSeeder::class,
        ]);
    }
}
