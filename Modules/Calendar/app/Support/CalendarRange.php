<?php

namespace Modules\Calendar\Support;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class CalendarRange
{
    private const CALENDAR_WEEKS = 6;

    private const DAYS_PER_WEEK = 7;

    /** @return Collection<int, CarbonImmutable> */
    public function calendarDays(CarbonImmutable $month): Collection
    {
        $start = $month->startOfMonth()->startOfWeek(CarbonImmutable::SUNDAY);

        return collect(range(0, self::CALENDAR_WEEKS * self::DAYS_PER_WEEK - 1))
            ->map(fn (int $offset): CarbonImmutable => $start->addDays($offset));
    }

    /** @return Collection<int, CarbonImmutable> */
    public function monthDays(CarbonImmutable $month): Collection
    {
        $start = $month->startOfMonth();

        return collect(range(0, $start->daysInMonth - 1))
            ->map(fn (int $offset): CarbonImmutable => $start->addDays($offset));
    }

    /** @return Collection<int, CarbonImmutable> */
    public function weekDays(CarbonImmutable $date): Collection
    {
        $start = $date->startOfWeek(CarbonImmutable::MONDAY);

        return collect(range(0, self::DAYS_PER_WEEK - 1))
            ->map(fn (int $offset): CarbonImmutable => $start->addDays($offset));
    }
}
