<?php

use Carbon\CarbonImmutable;
use Modules\Calendar\Support\CalendarRange;

it('builds the six week calendar range from Sunday', function (): void {
    $days = (new CalendarRange)->calendarDays(CarbonImmutable::parse('2026-09-01'));

    expect($days)->toHaveCount(42)
        ->and($days->first()->toDateString())->toBe('2026-08-30')
        ->and($days->last()->toDateString())->toBe('2026-10-10');
});

it('builds a month range containing only dates in that month', function (): void {
    $days = (new CalendarRange)->monthDays(CarbonImmutable::parse('2026-09-18'));

    expect($days)->toHaveCount(30)
        ->and($days->first()->toDateString())->toBe('2026-09-01')
        ->and($days->last()->toDateString())->toBe('2026-09-30');
});

it('builds a Monday-first week range including the weekend', function (): void {
    $days = (new CalendarRange)->weekDays(CarbonImmutable::parse('2026-10-02'));

    expect($days)->toHaveCount(7)
        ->and($days->first()->toDateString())->toBe('2026-09-28')
        ->and($days->last()->toDateString())->toBe('2026-10-04');
});
