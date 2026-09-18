<?php

namespace Modules\Calendar\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class CalendarRange
{
    private const CALENDAR_WEEKS = 6;

    private const DAYS_PER_WEEK = 7;

    private const GANTT_DAYS_EACH_SIDE = 10;

    /** @return Collection<int, CarbonImmutable> */
    public function calendarDays(CarbonImmutable $month): Collection
    {
        $start = $month->startOfMonth()->startOfWeek(CarbonImmutable::SUNDAY);

        return collect(range(0, self::CALENDAR_WEEKS * self::DAYS_PER_WEEK - 1))
            ->map(fn (int $offset): CarbonImmutable => $start->addDays($offset));
    }

    /** @return Collection<int, CarbonImmutable> */
    public function ganttDays(CarbonImmutable $centerDate): Collection
    {
        $start = $centerDate->subDays(self::GANTT_DAYS_EACH_SIDE);

        return collect(range(0, self::GANTT_DAYS_EACH_SIDE * 2))
            ->map(fn (int $offset): CarbonImmutable => $start->addDays($offset));
    }
}
