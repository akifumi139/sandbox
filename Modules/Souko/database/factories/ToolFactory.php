<?php

namespace Modules\Souko\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Souko\Models\Tool;

class ToolFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Tool::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'management_number' => fake()->unique()->bothify('T-######'),
            'name' => fake()->randomElement(['インパクトドライバー', '丸ノコ', '脚立', 'ハンマードリル']),
            'type' => fake()->randomElement(['電動工具', '切断工具', '脚立']),
            'model' => fake()->bothify('MODEL-###'),
            'manufacturer' => fake()->company(),
            'status' => fake()->randomElement(['available', 'rented', 'maintenance', 'disposed']),
        ];
    }

    public function ladder(): static
    {
        return $this->state(fn (array $attributes): array => [
            'management_number' => fake()->unique()->bothify('L-######'),
            'name' => '脚立',
            'type' => '脚立',
            'model' => fake()->randomElement(['4尺', '6尺', '8尺']),
            'manufacturer' => 'PiCa',
            'status' => 'available',
        ]);
    }
}
