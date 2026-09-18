<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Modules\Calendar\Actions\SaveTask;
use Modules\Calendar\Models\Task;

it('stores an inclusive task date range for its creator', function (): void {
    $user = User::factory()->create();

    $task = app(SaveTask::class)->handle(
        $user,
        '設計レビュー',
        now()->setDate(2026, 9, 21)->startOfDay(),
        now()->setDate(2026, 9, 24)->startOfDay(),
        '#F43F5E',
    );

    expect($task->user->is($user))->toBeTrue()
        ->and($task->start_date)->toBeInstanceOf(CarbonImmutable::class)
        ->and($task->start_date->toDateString())->toBe('2026-09-21')
        ->and($task->end_date->toDateString())->toBe('2026-09-24')
        ->and($task->color)->toBe('#F43F5E');
});

it('allows every user to view tasks but only the creator to modify them', function (): void {
    $task = Task::factory()->create();
    $otherUser = User::factory()->create();

    expect($otherUser->can('view', $task))->toBeTrue()
        ->and($otherUser->can('update', $task))->toBeFalse()
        ->and($otherUser->can('delete', $task))->toBeFalse()
        ->and($task->user->can('update', $task))->toBeTrue();
});
