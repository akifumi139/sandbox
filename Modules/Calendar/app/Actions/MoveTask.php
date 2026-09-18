<?php

namespace Modules\Calendar\Actions;

use Modules\Calendar\Models\Task;

class MoveTask
{
    public function handle(Task $task, int $days): Task
    {
        if ($days === 0) {
            return $task;
        }

        $task->fill([
            'start_date' => $task->start_date->addDays($days),
            'end_date' => $task->end_date->addDays($days),
        ])->save();

        return $task->refresh();
    }
}
