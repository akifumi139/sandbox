<?php

namespace Modules\Calendar\Support;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

final class CalendarLayoutBuilder
{
    private const DAYS_PER_WEEK = 7;

    private const MINIMUM_ROW_HEIGHT = 128;

    /** @return array{bars: array<int, array<string, mixed>>, rowHeights: array<int, int>} */
    public function build(EloquentCollection $tasks, Collection $days, ?int $ownerId): array
    {
        $gridStart = $days->first();
        $gridEnd = $days->last();
        $weekCount = intdiv($days->count(), self::DAYS_PER_WEEK);
        $rowLanes = array_fill(0, $weekCount, []);
        $bars = [];
        $lastDayIndex = $days->count() - 1;

        foreach ($tasks as $task) {
            $index = $task->start_date->lessThan($gridStart)
                ? 0
                : $gridStart->diffInDays($task->start_date);
            $endIndex = $task->end_date->greaterThan($gridEnd)
                ? $lastDayIndex
                : $gridStart->diffInDays($task->end_date);

            while ($index <= $endIndex) {
                $rowIndex = intdiv($index, self::DAYS_PER_WEEK);
                $columnIndex = $index % self::DAYS_PER_WEEK;
                $rowEndIndex = min($endIndex, (($rowIndex + 1) * self::DAYS_PER_WEEK) - 1);
                $span = $rowEndIndex - $index + 1;
                $lane = 0;

                while (($rowLanes[$rowIndex][$lane] ?? 0) > $columnIndex) {
                    $lane++;
                }

                $rowLanes[$rowIndex][$lane] = $columnIndex + $span;
                $bars[] = [
                    'id' => $task->id,
                    'name' => $task->name,
                    'row' => $rowIndex,
                    'column' => $columnIndex,
                    'span' => $span,
                    'lane' => $lane,
                    'color' => $task->color,
                    'isOwner' => $task->user_id === $ownerId,
                ];
                $index = $rowEndIndex + 1;
            }
        }

        $rowHeights = array_map(
            fn (array $lanes): int => max(self::MINIMUM_ROW_HEIGHT, 36 + (count($lanes) * 28) + 8),
            $rowLanes,
        );

        return compact('bars', 'rowHeights');
    }
}
