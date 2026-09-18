<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Calendar\Livewire\TaskManager;
use Modules\Calendar\Models\Task;

it('serves the task manager from the calendar route', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('calendar.index'))
        ->assertSuccessful()
        ->assertSeeLivewire(TaskManager::class);
});

it('defaults to the calendar view and accepts the view query parameter', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertSet('viewMode', 'calendar');

    Livewire::actingAs($user)
        ->withQueryParams(['view' => 'gantt'])
        ->test(TaskManager::class)
        ->assertSet('viewMode', 'gantt');
});

it('builds an inclusive gantt range and six week calendar layout', function (): void {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create([
        'name' => '月またぎタスク',
        'start_date' => '2026-09-28',
        'end_date' => '2026-10-05',
    ]);

    $component = Livewire::actingAs($user)
        ->withQueryParams(['month' => '2026-09', 'center' => '2026-09-30'])
        ->test(TaskManager::class);

    expect($component->get('ganttDays'))->toHaveCount(21)
        ->and($component->get('ganttTasks')->first()->is($task))->toBeTrue()
        ->and($component->get('calendarDays'))->toHaveCount(42)
        ->and($component->get('calendarDays')->first()->toDateString())->toBe('2026-08-30')
        ->and($component->get('calendarLayout.bars'))->toHaveCount(2);
});

it('includes tasks touching either edge of each visible range', function (): void {
    $user = User::factory()->create();
    $before = Task::factory()->for($user)->create(['start_date' => '2026-08-29', 'end_date' => '2026-08-29']);
    $first = Task::factory()->for($user)->create(['start_date' => '2026-08-29', 'end_date' => '2026-08-30']);
    $last = Task::factory()->for($user)->create(['start_date' => '2026-10-10', 'end_date' => '2026-10-11']);
    Task::factory()->for($user)->create(['start_date' => '2026-10-11', 'end_date' => '2026-10-11']);
    $ganttFirst = Task::factory()->for($user)->create(['start_date' => '2026-09-09', 'end_date' => '2026-09-10']);
    $ganttLast = Task::factory()->for($user)->create(['start_date' => '2026-09-30', 'end_date' => '2026-10-01']);

    $component = Livewire::actingAs($user)
        ->withQueryParams(['month' => '2026-09', 'center' => '2026-09-20'])
        ->test(TaskManager::class);

    expect($component->get('calendarTasks')->modelKeys())->toBe([$first->id, $ganttFirst->id, $ganttLast->id, $last->id])
        ->and($component->get('ganttTasks')->modelKeys())->toBe([$ganttFirst->id, $ganttLast->id]);
});

it('moves and resizes a task only for its creator', function (): void {
    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create([
        'start_date' => '2026-09-21',
        'end_date' => '2026-09-24',
    ]);

    Livewire::actingAs($owner)
        ->test(TaskManager::class)
        ->call('moveTaskByDays', $task->id, 2)
        ->call('resizeTask', $task->id, 'right', '2026-09-28')
        ->assertHasNoErrors();

    expect($task->fresh()->start_date->toDateString())->toBe('2026-09-23')
        ->and($task->fresh()->end_date->toDateString())->toBe('2026-09-28');

    Livewire::actingAs(User::factory()->create())
        ->test(TaskManager::class)
        ->call('moveTaskByDays', $task->id, 1)
        ->assertForbidden();
});
