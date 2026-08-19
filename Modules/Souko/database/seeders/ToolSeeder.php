<?php

namespace Modules\Souko\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Souko\Models\Tool;

class ToolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tools = [
            [
                'management_number' => 'T-000123',
                'name' => 'インパクトドライバー',
                'model' => 'TD172DRGX',
                'manufacturer' => 'Makita',
                'status' => 'available',
            ],
            [
                'management_number' => 'T-000456',
                'name' => 'ハンマードリル',
                'model' => 'HR2630',
                'manufacturer' => 'Makita',
                'status' => 'available',
            ],
            [
                'management_number' => 'T-000789',
                'name' => '丸ノコ',
                'model' => 'HS6303',
                'manufacturer' => 'Makita',
                'status' => 'available',
            ],
        ];

        foreach ($tools as $toolData) {
            Tool::query()->updateOrCreate(
                ['management_number' => $toolData['management_number']],
                $toolData,
            );
        }
    }
}
