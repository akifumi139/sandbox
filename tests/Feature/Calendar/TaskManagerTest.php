<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Calendar\Livewire\TaskForm;
use Modules\Calendar\Livewire\TaskManager;
use Modules\Calendar\Models\Task;

it('serves the task manager from the calendar route', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar.index'))
        ->assertSuccessful()
        ->assertSeeLivewire(TaskManager::class);
});

it('defaults to the month view and accepts supported and legacy view parameters', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertSet('viewMode', 'month');

    Livewire::actingAs($user)
        ->withQueryParams(['view' => 'week'])
        ->test(TaskManager::class)
        ->assertSet('viewMode', 'week');

    Livewire::actingAs($user)
        ->withQueryParams(['view' => 'day'])
        ->test(TaskManager::class)
        ->assertSet('viewMode', 'day');

    Livewire::actingAs($user)
        ->withQueryParams(['view' => 'gantt'])
        ->test(TaskManager::class)
        ->assertSet('viewMode', 'month');
});

it('builds month, week, and day task ranges', function (): void {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create([
        'name' => '月またぎタスク',
        'start_date' => '2026-09-28',
        'end_date' => '2026-10-05',
    ]);

    $component = Livewire::actingAs($user)
        ->withQueryParams(['month' => '2026-09', 'center' => '2026-09-30'])
        ->test(TaskManager::class);

    expect($component->get('monthDays'))->toHaveCount(30)
        ->and($component->get('monthDays')->first()->toDateString())->toBe('2026-09-01')
        ->and($component->get('monthDays')->last()->toDateString())->toBe('2026-09-30')
        ->and($component->get('monthTasks')->first()->is($task))->toBeTrue()
        ->and($component->get('weekDays'))->toHaveCount(7)
        ->and($component->get('weekDays')->first()->toDateString())->toBe('2026-09-28')
        ->and($component->get('weekTasks')->first()->is($task))->toBeTrue()
        ->and($component->get('dayTasks')->first()->is($task))->toBeTrue();
});

it('includes tasks touching the first and last date of visible month and week ranges', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->create(['start_date' => '2026-08-30', 'end_date' => '2026-08-31']);
    $monthFirst = Task::factory()->for($user)->create(['start_date' => '2026-08-31', 'end_date' => '2026-09-01']);
    $weekFirst = Task::factory()->for($user)->create(['start_date' => '2026-09-13', 'end_date' => '2026-09-14']);
    $weekLast = Task::factory()->for($user)->create(['start_date' => '2026-09-20', 'end_date' => '2026-09-21']);
    $monthLast = Task::factory()->for($user)->create(['start_date' => '2026-09-30', 'end_date' => '2026-10-01']);
    Task::factory()->for($user)->create(['start_date' => '2026-10-01', 'end_date' => '2026-10-01']);

    $component = Livewire::actingAs($user)
        ->withQueryParams(['month' => '2026-09', 'center' => '2026-09-20'])
        ->test(TaskManager::class);

    expect($component->get('monthTasks')->modelKeys())->toBe([$monthFirst->id, $weekFirst->id, $weekLast->id, $monthLast->id])
        ->and($component->get('weekTasks')->modelKeys())->toBe([$weekFirst->id, $weekLast->id]);
});

it('moves and resizes a task only for its creator', function (): void {
    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create([
        'start_date' => '2026-09-21',
        'end_date' => '2026-09-24',
        'start_time' => '09:30',
        'end_time' => '17:00',
    ]);

    Livewire::actingAs($owner)
        ->test(TaskManager::class)
        ->call('moveTaskByDays', $task->id, 2)
        ->call('resizeTask', $task->id, 'right', '2026-09-28')
        ->assertHasNoErrors();

    expect($task->fresh()->start_date->toDateString())->toBe('2026-09-23')
        ->and($task->fresh()->end_date->toDateString())->toBe('2026-09-28')
        ->and($task->fresh()->start_time)->toStartWith('09:30')
        ->and($task->fresh()->end_time)->toStartWith('17:00');

    Livewire::actingAs(User::factory()->create())
        ->test(TaskManager::class)
        ->call('moveTaskByDays', $task->id, 1)
        ->assertForbidden();
});

it('creates all-day tasks by default and clears optional times', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TaskForm::class)
        ->call('open', null, '2026-09-21')
        ->assertSet('form.schedule_type', 'all_day')
        ->set('form.name', '終日タスク')
        ->call('save')
        ->assertHasNoErrors();

    $task = Task::query()->where('name', '終日タスク')->sole();

    expect($task->start_time)->toBeNull()
        ->and($task->end_time)->toBeNull();
});

it('creates a timed task that crosses midnight', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TaskForm::class)
        ->call('open', null, '2026-09-21')
        ->set('form.name', '夜間タスク')
        ->set('form.schedule_type', 'timed')
        ->set('form.start_time', '23:00')
        ->set('form.end_date', '2026-09-22')
        ->set('form.end_time', '01:00')
        ->call('save')
        ->assertHasNoErrors();

    $task = Task::query()->where('name', '夜間タスク')->sole();

    expect($task->start_time)->toStartWith('23:00')
        ->and($task->end_time)->toStartWith('01:00');
});

it('shows clock labels in month, week, and day views', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->create([
        'start_date' => '2026-09-21',
        'end_date' => '2026-09-21',
        'start_time' => '09:30',
        'end_time' => '11:00',
    ]);

    $component = Livewire::actingAs($user)
        ->withQueryParams(['month' => '2026-09', 'center' => '2026-09-21'])
        ->test(TaskManager::class)
        ->assertSee('09:30 - 11:00');

    $component->set('viewMode', 'week')
        ->assertSee('09:30 - 11:00');

    $component->set('viewMode', 'day')
        ->assertSee('09:30 - 11:00');
});

it('navigates by the active view and opens a selected day', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->withQueryParams(['month' => '2026-09', 'center' => '2026-09-30', 'view' => 'week'])
        ->test(TaskManager::class)
        ->call('previousPeriod')
        ->assertSet('currentCenterDate', '2026-09-23')
        ->call('showDay', '2026-09-21')
        ->assertSet('viewMode', 'day')
        ->assertSet('currentCenterDate', '2026-09-21')
        ->call('previousPeriod')
        ->assertSet('currentCenterDate', '2026-09-20');
});

it('moves the month and synchronizes the week and day cursor', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->withQueryParams(['month' => '2026-09', 'center' => '2026-09-30', 'view' => 'month'])
        ->test(TaskManager::class)
        ->call('previousPeriod')
        ->assertSet('currentMonth', '2026-08')
        ->assertSet('currentCenterDate', '2026-08-01')
        ->call('nextPeriod')
        ->assertSet('currentMonth', '2026-09')
        ->assertSet('currentCenterDate', '2026-09-01');
});

it('synchronizes the month when week navigation crosses a month boundary', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->withQueryParams(['month' => '2026-10', 'center' => '2026-10-30', 'view' => 'week'])
        ->test(TaskManager::class)
        ->call('nextPeriod')
        ->assertSet('currentCenterDate', '2026-11-06')
        ->assertSet('currentMonth', '2026-11')
        ->set('viewMode', 'month')
        ->assertSee('2026年11月');
});

it('renders Saturday and Sunday as cards in the responsive week grid', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->create([
        'name' => '土曜の作業',
        'start_date' => '2026-10-03',
        'end_date' => '2026-10-03',
    ]);
    Task::factory()->for($user)->create([
        'name' => '日曜の作業',
        'start_date' => '2026-10-04',
        'end_date' => '2026-10-04',
    ]);

    Livewire::actingAs($user)
        ->withQueryParams(['center' => '2026-10-02', 'view' => 'week'])
        ->test(TaskManager::class)
        ->assertSee('土曜の作業')
        ->assertSee('日曜の作業')
        ->assertSee('10/3')
        ->assertSee('10/4')
        ->assertSeeHtml('sm:grid-cols-2')
        ->assertSeeHtml('xl:grid-cols-4');
});

it('clips timed tasks to the selected day and assigns non-overlapping lanes', function (): void {
    $user = User::factory()->create();
    $overnight = Task::factory()->for($user)->create([
        'start_date' => '2026-09-20',
        'end_date' => '2026-09-21',
        'start_time' => '22:00',
        'end_time' => '02:00',
    ]);
    $overlapping = Task::factory()->for($user)->create([
        'start_date' => '2026-09-21',
        'end_date' => '2026-09-21',
        'start_time' => '01:00',
        'end_time' => '03:00',
    ]);

    $component = Livewire::actingAs($user)
        ->withQueryParams(['center' => '2026-09-21', 'view' => 'day'])
        ->test(TaskManager::class)
        ->assertSee('00:00 - 02:00');
    $schedule = $component->get('daySchedule');

    expect($schedule)->toHaveCount(2)
        ->and($schedule[0]['task']->is($overnight))->toBeTrue()
        ->and($schedule[0]['start'])->toBe(0)
        ->and($schedule[0]['end'])->toBe(120)
        ->and($schedule[1]['task']->is($overlapping))->toBeTrue()
        ->and($schedule[1]['lane'])->toBe(1);
});

it('shows assignee names for all-day and timed tasks in the day view', function (): void {
    $user = User::factory()->create();
    Task::factory()->for($user)->create([
        'name' => '終日作業',
        'start_date' => '2026-09-21',
        'end_date' => '2026-09-21',
        'assignees' => ['大野'],
    ]);
    Task::factory()->for($user)->create([
        'name' => '時間指定作業',
        'start_date' => '2026-09-21',
        'end_date' => '2026-09-21',
        'start_time' => '09:00',
        'end_time' => '09:30',
        'assignees' => ['佐藤', '山田'],
    ]);

    Livewire::actingAs($user)
        ->withQueryParams(['center' => '2026-09-21', 'view' => 'day'])
        ->test(TaskManager::class)
        ->assertSee('終日作業')
        ->assertSee('時間指定作業')
        ->assertSee('担当 大野')
        ->assertSee('担当 佐藤、山田');
});

it('rejects a timed task whose end is not after its start', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TaskForm::class)
        ->call('open', null, '2026-09-21')
        ->set('form.name', '不正な時間')
        ->set('form.schedule_type', 'timed')
        ->set('form.start_time', '10:00')
        ->set('form.end_time', '10:00')
        ->call('save')
        ->assertHasErrors(['form.end_time']);
});

it('requires both times when a task is timed', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TaskForm::class)
        ->call('open', null, '2026-09-21')
        ->set('form.name', '時刻が不足したタスク')
        ->set('form.schedule_type', 'timed')
        ->set('form.start_time', '09:00')
        ->set('form.end_time', '')
        ->call('save')
        ->assertHasErrors(['form.end_time']);
});
