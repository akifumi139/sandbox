<?php

namespace Modules\Heya\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Heya\Models\Room;

/** @extends Factory<Room> */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->bothify('会議室 ??-##'),
            'is_active' => true,
        ];
    }
}
