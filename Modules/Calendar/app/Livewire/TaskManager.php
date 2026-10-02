<?php

namespace Modules\Calendar\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Calendar\Actions\MoveTask;
use Modules\Calendar\Actions\ResizeTask;
use Modules\Calendar\Models\Task;
use Modules\Calendar\Support\CalendarRange;

class TaskManager extends Component
{
    #[Url(as: 'month', history: true)]
    public string $currentMonth = '';

    #[Url(as: 'center', history: true)]
    public string $currentCenterDate = '';

    #[Url(as: 'view', history: true)]
    public string $viewMode = 'month';

    public function mount(): void
    {
        $this->currentMonth = $this->currentMonth !== ''
            ? CarbonImmutable::parse($this->currentMonth.'-01')->format('Y-m')
            : now()->format('Y-m');
        $this->currentCenterDate = $this->currentCenterDate !== ''
            ? CarbonImmutable::parse($this->currentCenterDate)->toDateString()
            : now()->toDateString();
        $this->viewMode = match ($this->viewMode) {
            'month', 'week', 'day' => $this->viewMode,
            'calendar', 'gantt' => 'month',
            default => 'month',
        };
    }

    public function previousPeriod(): void
    {
        if ($this->viewMode === 'month') {
            $month = $this->month()->subMonth();
            $this->currentMonth = $month->format('Y-m');
            $this->currentCenterDate = $month->toDateString();
        } else {
            $this->currentCenterDate = $this->viewMode === 'week'
                ? $this->centerDate()->subWeek()->toDateString()
                : $this->centerDate()->subDay()->toDateString();
        }

        $this->currentMonth = $this->centerDate()->format('Y-m');
        $this->clearTaskData();
    }

    public function nextPeriod(): void
    {
        if ($this->viewMode === 'month') {
            $month = $this->month()->addMonth();
            $this->currentMonth = $month->format('Y-m');
            $this->currentCenterDate = $month->toDateString();
        } else {
            $this->currentCenterDate = $this->viewMode === 'week'
                ? $this->centerDate()->addWeek()->toDateString()
                : $this->centerDate()->addDay()->toDateString();
        }

        $this->currentMonth = $this->centerDate()->format('Y-m');
        $this->clearTaskData();
    }

    public function today(): void
    {
        $this->currentMonth = now()->format('Y-m');
        $this->currentCenterDate = now()->toDateString();
        $this->clearTaskData();
    }

    public function showDay(string $date): void
    {
        $this->currentCenterDate = CarbonImmutable::createFromFormat('!Y-m-d', $date)->toDateString();
        $this->currentMonth = $this->centerDate()->format('Y-m');
        $this->viewMode = 'day';
        $this->clearTaskData();
    }

    #[On('task-saved')]
    #[On('task-deleted')]
    public function clearTaskData(): void
    {
        $this->clearMonthData();
        $this->clearWeekData();
        $this->clearDayData();
    }

    #[Renderless]
    public function moveTaskByDays(int $taskId, int $days, MoveTask $moveTask): void
    {
        if ($days === 0) {
            return;
        }

        $task = Task::query()->whereKey($taskId)->firstOrFail();
        Gate::authorize('update', $task);

        $moveTask->handle($task, $days);

        $this->clearTaskData();
    }

    #[Renderless]
    public function resizeTask(int $taskId, string $edge, string $date, ResizeTask $resizeTask): void
    {
        $task = Task::query()->whereKey($taskId)->firstOrFail();
        Gate::authorize('update', $task);

        $targetDate = CarbonImmutable::createFromFormat('!Y-m-d', $date);

        $resizeTask->handle($task, $edge, $targetDate);

        $this->clearTaskData();
    }

    /** @return Collection<int, CarbonImmutable> */
    #[Computed]
    public function monthDays(): Collection
    {
        return app(CalendarRange::class)->monthDays($this->month());
    }

    /** @return Collection<int, CarbonImmutable> */
    #[Computed]
    public function weekDays(): Collection
    {
        return app(CalendarRange::class)->weekDays($this->centerDate());
    }

    /** @return EloquentCollection<int, Task> */
    #[Computed]
    public function monthTasks(): EloquentCollection
    {
        $days = $this->monthDays();

        return $this->tasksOverlapping($days->first(), $days->last());
    }

    /** @return EloquentCollection<int, Task> */
    #[Computed]
    public function weekTasks(): EloquentCollection
    {
        $days = $this->weekDays();

        return $this->tasksOverlapping($days->first(), $days->last());
    }

    /** @return EloquentCollection<int, Task> */
    #[Computed]
    public function dayTasks(): EloquentCollection
    {
        $date = $this->centerDate();

        return $this->tasksOverlapping($date, $date);
    }

    /** @return array<int, array{task: Task, start: int, end: int, lane: int, laneCount: int}> */
    #[Computed]
    public function daySchedule(): array
    {
        $date = $this->centerDate();
        $laneEnds = [];
        $scheduledTasks = $this->dayTasks()
            ->filter(fn (Task $task): bool => $task->start_time !== null && $task->end_time !== null)
            ->sortBy(fn (Task $task): string => $task->start_date->toDateString().' '.$task->start_time)
            ->map(function (Task $task) use ($date, &$laneEnds): array {
                $startTime = CarbonImmutable::parse($date->toDateString().' '.$task->start_time);
                $endTime = CarbonImmutable::parse($date->toDateString().' '.$task->end_time);
                $start = $task->start_date->lessThan($date) ? 0 : $startTime->hour * 60 + $startTime->minute;
                $end = $task->end_date->greaterThan($date) ? 1440 : $endTime->hour * 60 + $endTime->minute;

                $lane = 0;
                while (isset($laneEnds[$lane]) && $laneEnds[$lane] > $start) {
                    $lane++;
                }

                $laneEnds[$lane] = $end;

                return ['task' => $task, 'start' => $start, 'end' => $end, 'lane' => $lane];
            })
            ->values();
        $laneCount = max(1, count($laneEnds));

        return $scheduledTasks
            ->map(fn (array $scheduledTask): array => [...$scheduledTask, 'laneCount' => $laneCount])
            ->all();
    }

    public function month(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->currentMonth.'-01');
    }

    public function centerDate(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->currentCenterDate);
    }

    /** @return EloquentCollection<int, Task> */
    private function tasksOverlapping(CarbonImmutable $start, CarbonImmutable $end): EloquentCollection
    {
        return Task::query()
            ->with('user')
            ->overlapping($start, $end)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get();
    }

    private function clearMonthData(): void
    {
        unset($this->monthDays, $this->monthTasks);
    }

    private function clearWeekData(): void
    {
        unset($this->weekDays, $this->weekTasks);
    }

    private function clearDayData(): void
    {
        unset($this->dayTasks, $this->daySchedule);
    }

    public function render(): View
    {
        return view('calendar::livewire.task-manager');
    }
}
