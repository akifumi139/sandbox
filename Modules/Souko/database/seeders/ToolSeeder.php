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
                'type' => '電動工具',
                'model' => 'TD172DRGX',
                'manufacturer' => 'Makita',
                'status' => 'available',
            ],
            [
                'management_number' => 'T-000456',
                'name' => 'ハンマードリル',
                'type' => '電動工具',
                'model' => 'HR2630',
                'manufacturer' => 'Makita',
                'status' => 'available',
            ],
            [
                'management_number' => 'T-000789',
                'name' => '丸ノコ',
                'type' => '切断工具',
                'model' => 'HS6303',
                'manufacturer' => 'Makita',
                'status' => 'available',
            ],
            [
                'management_number' => 'L-000001',
                'name' => '脚立',
                'type' => '脚立',
                'model' => '6尺',
                'manufacturer' => 'PiCa',
                'status' => 'available',
            ],
            [
                'management_number' => 'L-000002',
                'name' => '脚立',
                'type' => '脚立',
                'model' => '6尺',
                'manufacturer' => 'PiCa',
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
