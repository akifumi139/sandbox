<?php

namespace Modules\Calendar\Actions;

use App\Models\User;
use Carbon\CarbonInterface;
use Modules\Calendar\Models\Task;

class SaveTask
{
    public function handle(
        User $user,
        string $name,
        CarbonInterface $startDate,
        CarbonInterface $endDate,
        string $color,
        ?Task $task = null,
    ): Task {
        $savedTask = $task ?? new Task;

        if (! $savedTask->exists) {
            $savedTask->user()->associate($user);
        }

        $savedTask->fill([
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'color' => $color,
        ])->save();

        return $savedTask->refresh();
    }
}
