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
        'user_id' => $user->id,
        'user_name' => $user->name,
        'borrow_at' => now()->subMinutes(30),
        'return_at' => null,
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

    $toolLog = ToolLog::query()->create([
        'tool_id' => $tool->id,
        'user_id' => $user->id,
        'user_name' => $user->name,
        'borrow_at' => now()->subMinutes(15),
        'return_at' => null,
    ]);

    Livewire::test(ReturnCounter::class)
        ->call('returnTool', $tool->id)
        ->assertSee('返却を完了しました。');

    expect($tool->fresh()->status)->toBe('available')
        ->and($toolLog->fresh()->return_at)->not->toBeNull()
        ->and(ToolLog::query()->where('tool_id', $tool->id)->count())->toBe(1);
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
        'user_id' => $user->id,
        'user_name' => $user->name,
        'borrow_at' => now()->subDays(10),
        'return_at' => null,
    ]);

    ToolLog::query()->create([
        'tool_id' => $tool->id,
        'user_id' => $user->id,
        'user_name' => $user->name,
        'borrow_at' => now()->subDay(),
        'return_at' => null,
    ]);

    $component = Livewire::test(ReturnCounter::class);

    $component->assertSee('重複履歴工具');

    expect(ToolLog::query()->where('tool_id', $tool->id)->count())->toBe(2)
        ->and($component->instance()->borrowedTools()->total())->toBe(1);
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

    $firstToolLog = ToolLog::query()->create([
        'tool_id' => $tool1->id,
        'user_id' => User::factory()->create(['name' => '田中 一郎'])->id,
        'user_name' => '田中 一郎',
        'borrow_at' => now()->subMinutes(20),
        'return_at' => null,
    ]);

    $secondToolLog = ToolLog::query()->create([
        'tool_id' => $tool2->id,
        'user_id' => User::factory()->create(['name' => '佐藤 二郎'])->id,
        'user_name' => '佐藤 二郎',
        'borrow_at' => now()->subMinutes(10),
        'return_at' => null,
    ]);

    Livewire::test(ReturnCounter::class)
        ->set('selectedToolIds', [$tool1->id, $tool2->id])
        ->call('bulkReturn')
        ->assertSee('2件の返却を完了しました。');

    expect($tool1->fresh()->status)->toBe('available')
        ->and($tool2->fresh()->status)->toBe('available')
        ->and($firstToolLog->fresh()->return_at)->not->toBeNull()
        ->and($secondToolLog->fresh()->return_at)->not->toBeNull();
});

it('does not return the same borrowing twice', function (): void {
    $user = User::factory()->create();
    $tool = Tool::query()->create([
        'management_number' => 'T-900014',
        'name' => '二重返却防止工具',
        'status' => 'rented',
    ]);

    $toolLog = ToolLog::query()->create([
        'tool_id' => $tool->id,
        'user_id' => $user->id,
        'user_name' => $user->name,
        'borrow_at' => now()->subMinute(),
        'return_at' => null,
    ]);

    Livewire::test(ReturnCounter::class)
        ->call('returnTool', $tool->id)
        ->call('returnTool', $tool->id)
        ->assertSee('この工具は返却できません。');

    expect($toolLog->fresh()->return_at)->not->toBeNull()
        ->and(ToolLog::query()->where('tool_id', $tool->id)->count())->toBe(1);
});
