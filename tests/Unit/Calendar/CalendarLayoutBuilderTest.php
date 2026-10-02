<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Modules\Calendar\Models\Task;
use Modules\Calendar\Support\CalendarLayoutBuilder;
use Modules\Calendar\Support\CalendarRange;

it('splits tasks across calendar weeks', function (): void {
    $range = new CalendarRange;
    $days = $range->calendarDays(CarbonImmutable::parse('2026-09-01'));
    $task = Task::make([
        'name' => '月またぎタスク',
        'start_date' => CarbonImmutable::parse('2026-09-28'),
        'end_date' => CarbonImmutable::parse('2026-10-05'),
        'color' => '#10B981',
        'user_id' => 7,
    ]);
    $task->id = 1;

    $layout = (new CalendarLayoutBuilder)->build(new Collection([$task]), $days, 7);

    expect($layout['bars'])->toHaveCount(2)
        ->and($layout['bars'][0])->toMatchArray([
            'id' => 1,
            'row' => 4,
            'column' => 1,
            'span' => 6,
            'lane' => 0,
            'isOwner' => true,
        ])
        ->and($layout['bars'][1])->toMatchArray([
            'row' => 5,
            'column' => 0,
            'span' => 2,
            'lane' => 0,
        ]);
});

it('places overlapping tasks in separate lanes', function (): void {
    $range = new CalendarRange;
    $days = $range->calendarDays(CarbonImmutable::parse('2026-09-01'));
    $tasks = new Collection([
        Task::make([
            'name' => '先のタスク',
            'start_date' => CarbonImmutable::parse('2026-09-01'),
            'end_date' => CarbonImmutable::parse('2026-09-03'),
            'color' => '#10B981',
            'user_id' => 7,
        ]),
        Task::make([
            'name' => '重なるタスク',
            'start_date' => CarbonImmutable::parse('2026-09-02'),
            'end_date' => CarbonImmutable::parse('2026-09-04'),
            'color' => '#0EA5E9',
            'user_id' => 8,
        ]),
    ]);

    $tasks[0]->id = 1;
    $tasks[1]->id = 2;

    $layout = (new CalendarLayoutBuilder)->build($tasks, $days, 7);

    expect($layout['bars'][0]['lane'])->toBe(0)
        ->and($layout['bars'][1]['lane'])->toBe(1)
        ->and($layout['rowHeights'][0])->toBe(128);
});

it('includes clock labels for timed tasks without changing day-based placement', function (): void {
    $days = (new CalendarRange)->calendarDays(CarbonImmutable::parse('2026-09-01'));
    $task = Task::make([
        'name' => '時刻付きタスク',
        'start_date' => CarbonImmutable::parse('2026-09-10'),
        'end_date' => CarbonImmutable::parse('2026-09-11'),
        'start_time' => '09:30',
        'end_time' => '11:00',
        'color' => '#10B981',
        'user_id' => 7,
    ]);
    $task->id = 3;

    $layout = (new CalendarLayoutBuilder)->build(new Collection([$task]), $days, 7);

    expect($layout['bars'][0])->toMatchArray([
        'column' => 4,
        'span' => 2,
        'timeLabel' => '09:30 - 11:00',
    ]);
});
