<?php

namespace Modules\Calendar\Livewire;

use App\Models\User;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Calendar\Actions\SaveTask;
use Modules\Calendar\Models\Task;

class TaskForm extends Component
{
    public bool $showModal = false;

    public bool $readOnly = false;

    public ?int $taskId = null;

    /** @var array{name: string, start_date: string, end_date: string, schedule_type: string, start_time: string, end_time: string, assignees: string, color: string} */
    public array $form = [
        'name' => '',
        'start_date' => '',
        'end_date' => '',
        'schedule_type' => 'all_day',
        'start_time' => '',
        'end_time' => '',
        'assignees' => '',
        'color' => '#10B981',
    ];

    #[On('open-task-form')]
    public function open(?int $taskId = null, ?string $date = null): void
    {
        $this->resetValidation();
        $this->taskId = $taskId;
        $this->readOnly = false;

        if ($taskId !== null) {
            $task = Task::query()->whereKey($taskId)->firstOrFail();
            Gate::authorize('view', $task);

            $this->readOnly = Gate::denies('update', $task);
            $this->form = [
                'name' => $task->name,
                'start_date' => $task->start_date->toDateString(),
                'end_date' => $task->end_date->toDateString(),
                'schedule_type' => $task->start_time === null && $task->end_time === null ? 'all_day' : 'timed',
                'start_time' => $task->start_time === null ? '' : substr($task->start_time, 0, 5),
                'end_time' => $task->end_time === null ? '' : substr($task->end_time, 0, 5),
                'assignees' => implode("\n", $task->assignees ?? []),
                'color' => $task->color,
            ];
        } else {
            Gate::authorize('create', Task::class);

            $taskDate = CarbonImmutable::parse($date ?? now()->toDateString())->toDateString();
            $this->form = [
                'name' => '',
                'start_date' => $taskDate,
                'end_date' => $taskDate,
                'schedule_type' => 'all_day',
                'start_time' => '',
                'end_time' => '',
                'assignees' => '',
                'color' => '#10B981',
            ];
        }

        $this->showModal = true;
    }

    public function save(SaveTask $saveTask): void
    {
        $task = $this->taskId === null
            ? null
            : Task::query()->whereKey($this->taskId)->firstOrFail();

        Gate::authorize($task === null ? 'create' : 'update', $task ?? Task::class);

        $validated = $this->validate();
        $isAllDay = $validated['form']['schedule_type'] === 'all_day';
        $assignees = collect(preg_split('/\R/u', $validated['form']['assignees'] ?? '') ?: [])
            ->map(fn (string $assignee): string => trim($assignee))
            ->filter()
            ->unique()
            ->values();

        if ($assignees->count() > 10 || $assignees->contains(fn (string $assignee): bool => Str::length($assignee) > 255)) {
            throw ValidationException::withMessages([
                'form.assignees' => '担当者は10名以内、各255文字以内で入力してください。',
            ]);
        }

        if (! $isAllDay) {
            $startsAt = CarbonImmutable::parse($validated['form']['start_date'].' '.$validated['form']['start_time']);
            $endsAt = CarbonImmutable::parse($validated['form']['end_date'].' '.$validated['form']['end_time']);

            if ($endsAt->lessThanOrEqualTo($startsAt)) {
                throw ValidationException::withMessages([
                    'form.end_time' => '終了日時は開始日時より後にしてください。',
                ]);
            }
        }

        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        $saveTask->handle(
            $user,
            trim($validated['form']['name']),
            CarbonImmutable::parse($validated['form']['start_date']),
            CarbonImmutable::parse($validated['form']['end_date']),
            strtoupper($validated['form']['color']),
            $task,
            startTime: $isAllDay ? null : $validated['form']['start_time'],
            endTime: $isAllDay ? null : $validated['form']['end_time'],
            assignees: $assignees->all(),
        );

        $this->showModal = false;
        $this->dispatch('task-saved');
        Flux::toast(variant: 'success', text: $task === null ? 'タスクを登録しました。' : 'タスクを更新しました。');
    }

    public function delete(): void
    {
        $task = Task::query()->whereKey($this->taskId)->firstOrFail();
        Gate::authorize('delete', $task);
        $task->delete();

        $this->showModal = false;
        $this->dispatch('task-deleted');
        Flux::toast(variant: 'success', text: 'タスクを削除しました。');
    }

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.start_date' => ['required', 'date_format:Y-m-d'],
            'form.end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:form.start_date'],
            'form.schedule_type' => ['required', 'in:all_day,timed'],
            'form.start_time' => $this->form['schedule_type'] === 'timed' ? ['required', 'date_format:H:i'] : ['nullable'],
            'form.end_time' => $this->form['schedule_type'] === 'timed' ? ['required', 'date_format:H:i'] : ['nullable'],
            'form.assignees' => ['nullable', 'string', 'max:2570'],
            'form.color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function render(): View
    {
        return view('calendar::livewire.task-form');
    }
}
