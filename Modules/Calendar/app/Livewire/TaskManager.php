<?php

namespace Modules\Calendar\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
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
use Modules\Calendar\Support\CalendarLayoutBuilder;
use Modules\Calendar\Support\CalendarRange;

class TaskManager extends Component
{
    #[Url(as: 'month', history: true)]
    public string $currentMonth = '';

    #[Url(as: 'center', history: true)]
    public string $currentCenterDate = '';

    #[Url(as: 'view', history: true)]
    public string $viewMode = 'calendar';

    public function mount(): void
    {
        $this->currentMonth = $this->currentMonth !== ''
            ? CarbonImmutable::parse($this->currentMonth.'-01')->format('Y-m')
            : now()->format('Y-m');
        $this->currentCenterDate = $this->currentCenterDate !== ''
            ? CarbonImmutable::parse($this->currentCenterDate)->toDateString()
            : now()->toDateString();
        $this->viewMode = in_array($this->viewMode, ['calendar', 'gantt'], true)
            ? $this->viewMode
            : 'calendar';
    }

    public function previousMonth(): void
    {
        $this->currentMonth = $this->month()->subMonth()->format('Y-m');
        $this->clearCalendarData();
    }

    public function nextMonth(): void
    {
        $this->currentMonth = $this->month()->addMonth()->format('Y-m');
        $this->clearCalendarData();
    }

    public function thisMonth(): void
    {
        $this->currentMonth = now()->format('Y-m');
        $this->clearCalendarData();
    }

    public function today(): void
    {
        $this->currentCenterDate = now()->toDateString();
        $this->clearGanttData();
    }

    public function previousGanttRange(): void
    {
        $this->currentCenterDate = $this->centerDate()->subDays(7)->toDateString();
        $this->clearGanttData();
    }

    public function nextGanttRange(): void
    {
        $this->currentCenterDate = $this->centerDate()->addDays(7)->toDateString();
        $this->clearGanttData();
    }

    #[On('task-saved')]
    #[On('task-deleted')]
    public function clearTaskData(): void
    {
        $this->clearCalendarData();
        $this->clearGanttData();
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
    public function calendarDays(): Collection
    {
        return app(CalendarRange::class)->calendarDays($this->month());
    }

    /** @return Collection<int, CarbonImmutable> */
    #[Computed]
    public function ganttDays(): Collection
    {
        return app(CalendarRange::class)->ganttDays($this->centerDate());
    }

    /** @return EloquentCollection<int, Task> */
    #[Computed]
    public function calendarTasks(): EloquentCollection
    {
        $days = $this->calendarDays();

        return $this->tasksOverlapping($days->first(), $days->last());
    }

    /** @return EloquentCollection<int, Task> */
    #[Computed]
    public function ganttTasks(): EloquentCollection
    {
        $days = $this->ganttDays();

        return $this->tasksOverlapping($days->first(), $days->last());
    }

    /** @return array{bars: array<int, array<string, mixed>>, rowHeights: array<int, int>} */
    #[Computed]
    public function calendarLayout(): array
    {
        $ownerId = Auth::id();

        return app(CalendarLayoutBuilder::class)->build(
            $this->calendarTasks(),
            $this->calendarDays(),
            $ownerId === null ? null : (int) $ownerId,
        );
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

    private function clearCalendarData(): void
    {
        unset($this->calendarDays, $this->calendarTasks, $this->calendarLayout);
    }

    private function clearGanttData(): void
    {
        unset($this->ganttDays, $this->ganttTasks);
    }

    public function render(): View
    {
        return view('calendar::livewire.task-manager');
    }
}
