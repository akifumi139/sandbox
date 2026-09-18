<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Calendar\Livewire\TaskForm;
use Modules\Calendar\Models\Task;

it('creates, updates, and deletes a task for its creator', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TaskForm::class)
        ->dispatch('open-task-form', date: '2026-09-21')
        ->set('form.name', '設計レビュー')
        ->set('form.end_date', '2026-09-24')
        ->set('form.color', '#F43F5E')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false)
        ->assertDispatched('task-saved');

    $task = Task::query()->sole();

    expect($task->color)->toBe('#F43F5E');

    Livewire::actingAs($user)
        ->test(TaskForm::class)
        ->dispatch('open-task-form', taskId: $task->id)
        ->set('form.name', '実施設計レビュー')
        ->call('save')
        ->assertDispatched('task-saved')
        ->call('delete')
        ->assertDispatched('task-deleted');

    expect($task->fresh())->toBeNull();
});

it('validates task dates and opens another users task read only', function (): void {
    $task = Task::factory()->create();
    $otherUser = User::factory()->create();

    Livewire::actingAs($otherUser)
        ->test(TaskForm::class)
        ->dispatch('open-task-form', taskId: $task->id)
        ->assertSet('showModal', true)
        ->assertSet('readOnly', true)
        ->assertSet('form.name', $task->name);

    Livewire::actingAs($otherUser)
        ->test(TaskForm::class)
        ->dispatch('open-task-form', date: '2026-09-24')
        ->set('form.name', '日付不正')
        ->set('form.end_date', '2026-09-23')
        ->call('save')
        ->assertHasErrors(['form.end_date']);
});
