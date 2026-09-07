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
        $json = file_get_contents(__DIR__.'/../data/tools.json');

        if ($json === false) {
            throw new \RuntimeException('工具データJSONを読み込めませんでした。');
        }

        $tools = json_decode(
            $json,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        foreach ($tools as $toolData) {
            Tool::query()->updateOrCreate(
                ['management_number' => $toolData['management_number']],
                $toolData,
            );
        }
    }
}
