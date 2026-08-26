<?php

namespace Modules\Syako\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Syako\Models\Vehicle;

/** @extends Factory<Vehicle> */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->bothify('車両 ??-##'),
            'vehicle_number' => fake()->unique()->bothify('品川 ??-##'),
            'manufacturer' => fake()->optional()->company(),
            'model' => fake()->optional()->word(),
            'model_code' => fake()->optional()->bothify('##-###'),
            'is_active' => true,
        ];
    }
}
