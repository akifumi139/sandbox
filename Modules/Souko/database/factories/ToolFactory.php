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
        return [];
    }
}
