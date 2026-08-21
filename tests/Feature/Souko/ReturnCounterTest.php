<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Souko\Livewire\ReturnCounter;
use Modules\Souko\Models\Tool;
use Modules\Souko\Models\ToolLog;

it('shows borrowed tools in the return list', function (): void {
    $user = User::factory()->create([
        'name' => '山田 太郎',
    ]);

    $tool = Tool::query()->create([
        'management_number' => 'T-900010',
        'name' => '返却対象工具',
        'model' => 'RTN-100',
        'manufacturer' => 'Test Corp',
        'status' => 'rented',
    ]);

    ToolLog::query()->create([
        'tool_id' => $tool->id,
        'action_type' => 'borrow',
        'user_id' => $user->id,
        'user_name' => $user->name,
        'logged_at' => now()->subMinutes(30),
    ]);

    Livewire::test(ReturnCounter::class)
        ->assertSee('返却対象工具')
        ->assertSee('T-900010')
        ->assertSee('山田 太郎');
});

it('marks a rented tool as available when returned', function (): void {
    $user = User::factory()->create([
        'name' => '鈴木 次郎',
    ]);

    $tool = Tool::query()->create([
        'management_number' => 'T-900011',
        'name' => '返却処理工具',
        'model' => 'RTN-200',
        'manufacturer' => 'Test Corp',
        'status' => 'rented',
    ]);

    ToolLog::query()->create([
        'tool_id' => $tool->id,
        'action_type' => 'borrow',
        'user_id' => $user->id,
        'user_name' => $user->name,
        'logged_at' => now()->subMinutes(15),
    ]);

    Livewire::test(ReturnCounter::class)
        ->call('returnTool', $tool->id)
        ->assertSee('返却を完了しました。');

    expect($tool->fresh()->status)->toBe('available')
        ->and(ToolLog::query()->where('tool_id', $tool->id)->where('action_type', 'return')->exists())->toBeTrue();
});

it('shows only the latest active borrow record for each tool', function (): void {
    $user = User::factory()->create(['name' => '田中 太郎']);

    $tool = Tool::query()->create([
        'management_number' => 'T-900012',
        'name' => '重複履歴工具',
        'model' => 'DUP-100',
        'manufacturer' => 'Test Corp',
        'status' => 'rented',
    ]);

    ToolLog::query()->create([
        'tool_id' => $tool->id,
        'action_type' => 'borrow',
        'user_id' => $user->id,
        'user_name' => $user->name,
        'logged_at' => now()->subDays(10),
    ]);

    ToolLog::query()->create([
        'tool_id' => $tool->id,
        'action_type' => 'borrow',
        'user_id' => $user->id,
        'user_name' => $user->name,
        'logged_at' => now()->subDay(),
    ]);

    $component = Livewire::test(ReturnCounter::class);

    $component->assertSee('重複履歴工具')
        ->assertDontSee('重複履歴工具', false);

    expect(ToolLog::query()->where('tool_id', $tool->id)->where('action_type', 'borrow')->count())->toBe(2)
        ->and($component->viewData('borrowedTools')->total())->toBe(1);
});

it('returns multiple selected tools in one action', function (): void {
    $tool1 = Tool::query()->create([
        'management_number' => 'T-900012',
        'name' => '一括返却工具1',
        'model' => 'BULK-100',
        'manufacturer' => 'Test Corp',
        'status' => 'rented',
    ]);

    $tool2 = Tool::query()->create([
        'management_number' => 'T-900013',
        'name' => '一括返却工具2',
        'model' => 'BULK-200',
        'manufacturer' => 'Test Corp',
        'status' => 'rented',
    ]);

    ToolLog::query()->create([
        'tool_id' => $tool1->id,
        'action_type' => 'borrow',
        'user_id' => User::factory()->create(['name' => '田中 一郎'])->id,
        'user_name' => '田中 一郎',
        'logged_at' => now()->subMinutes(20),
    ]);

    ToolLog::query()->create([
        'tool_id' => $tool2->id,
        'action_type' => 'borrow',
        'user_id' => User::factory()->create(['name' => '佐藤 二郎'])->id,
        'user_name' => '佐藤 二郎',
        'logged_at' => now()->subMinutes(10),
    ]);

    Livewire::test(ReturnCounter::class)
        ->set('selectedToolIds', [$tool1->id, $tool2->id])
        ->call('bulkReturn')
        ->assertSee('2件の返却を完了しました。');

    expect($tool1->fresh()->status)->toBe('available')
        ->and($tool2->fresh()->status)->toBe('available')
        ->and(ToolLog::query()->whereIn('tool_id', [$tool1->id, $tool2->id])->where('action_type', 'return')->count())->toBe(2);
});
