<?php

namespace Modules\Calendar\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Calendar\Models\Task;

/** @extends Factory<Task> */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+1 month');

        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'start_date' => $startDate,
            'end_date' => (clone $startDate)->modify('+'.fake()->numberBetween(0, 14).' days'),
            'color' => fake()->randomElement(['#10B981', '#0EA5E9', '#F59E0B', '#F43F5E', '#8B5CF6']),
        ];
    }
}
