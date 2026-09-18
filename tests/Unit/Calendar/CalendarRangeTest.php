<?php

use Carbon\CarbonImmutable;
use Modules\Calendar\Support\CalendarRange;

it('builds the six week calendar range from Sunday', function (): void {
    $days = (new CalendarRange)->calendarDays(CarbonImmutable::parse('2026-09-01'));

    expect($days)->toHaveCount(42)
        ->and($days->first()->toDateString())->toBe('2026-08-30')
        ->and($days->last()->toDateString())->toBe('2026-10-10');
});

it('builds an inclusive gantt range around the center date', function (): void {
    $days = (new CalendarRange)->ganttDays(CarbonImmutable::parse('2026-09-30'));

    expect($days)->toHaveCount(21)
        ->and($days->first()->toDateString())->toBe('2026-09-20')
        ->and($days->last()->toDateString())->toBe('2026-10-10');
});
