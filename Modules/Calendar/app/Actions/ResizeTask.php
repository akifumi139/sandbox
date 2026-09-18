<?php

namespace Modules\Calendar\Actions;

use Carbon\CarbonImmutable;
use Modules\Calendar\Models\Task;

class ResizeTask
{
    public function handle(Task $task, string $edge, CarbonImmutable $targetDate): Task
    {
        if ($edge === 'left' && $targetDate->lessThanOrEqualTo($task->end_date)) {
            $task->update(['start_date' => $targetDate]);
        }

        if ($edge === 'right' && $targetDate->greaterThanOrEqualTo($task->start_date)) {
            $task->update(['end_date' => $targetDate]);
        }

        return $task->refresh();
    }
}
