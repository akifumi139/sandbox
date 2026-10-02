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

it('normalizes and persists multiple free-form assignees', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TaskForm::class)
        ->call('open', null, '2026-09-21')
        ->assertSee('担当者（1行に1名）')
        ->assertDontSee('class="text-lg font-medium" />')
        ->set('form.name', '担当者のあるタスク')
        ->set('form.assignees', " 大野 \n佐藤\n大野\n ")
        ->call('save')
        ->assertHasNoErrors();

    $task = Task::query()->sole();

    expect($task->assignees)->toBe(['大野', '佐藤']);

    Livewire::actingAs($user)
        ->test(TaskForm::class)
        ->call('open', $task->id)
        ->assertSet('form.assignees', "大野\n佐藤")
        ->set('form.assignees', "山田\n営業")
        ->call('save')
        ->assertHasNoErrors();

    expect($task->fresh()->assignees)->toBe(['山田', '営業']);
});

it('limits assignee names to ten entries of at most 255 characters', function (): void {
    $user = User::factory()->create();
    $tooManyAssignees = implode("\n", range(1, 11));

    Livewire::actingAs($user)
        ->test(TaskForm::class)
        ->call('open', null, '2026-09-21')
        ->set('form.name', '担当者数の上限')
        ->set('form.assignees', $tooManyAssignees)
        ->call('save')
        ->assertHasErrors(['form.assignees']);

    Livewire::actingAs($user)
        ->test(TaskForm::class)
        ->call('open', null, '2026-09-21')
        ->set('form.name', '担当者名の上限')
        ->set('form.assignees', str_repeat('名', 256))
        ->call('save')
        ->assertHasErrors(['form.assignees']);
});
