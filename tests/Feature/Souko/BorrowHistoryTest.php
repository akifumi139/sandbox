<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Souko\Livewire\BorrowHistory;
use Modules\Souko\Models\Tool;
use Modules\Souko\Models\ToolLog;

it('shows borrowing and return as one lending record', function (): void {
    $user = User::factory()->create(['name' => '履歴 利用者']);
    $tool = Tool::query()->create([
        'management_number' => 'H-100001',
        'name' => 'セット表示工具',
        'status' => 'available',
    ]);
    $toolLog = ToolLog::query()->create([
        'tool_id' => $tool->id,
        'user_id' => $user->id,
        'user_name' => $user->name,
        'borrow_at' => now()->subHour(),
        'return_at' => now(),
    ]);

    $component = Livewire::test(BorrowHistory::class)
        ->assertSee('セット表示工具')
        ->assertSee('履歴 利用者')
        ->assertSee('返却済み');

    $logs = $component->instance()->logs();

    expect($logs->total())->toBe(1)
        ->and($logs->first()->id)->toBe($toolLog->id)
        ->and($logs->first()->return_at)->not->toBeNull();
});

it('filters lending records by return status and search text', function (): void {
    $user = User::factory()->create(['name' => '検索 利用者']);
    $activeTool = Tool::query()->create([
        'management_number' => 'H-100002',
        'name' => '貸出中工具',
        'status' => 'rented',
    ]);
    $returnedTool = Tool::query()->create([
        'management_number' => 'H-100003',
        'name' => '返却済工具',
        'status' => 'available',
    ]);

    ToolLog::query()->create([
        'tool_id' => $activeTool->id,
        'user_id' => $user->id,
        'user_name' => $user->name,
        'borrow_at' => now()->subHours(2),
        'return_at' => null,
    ]);
    ToolLog::query()->create([
        'tool_id' => $returnedTool->id,
        'user_id' => $user->id,
        'user_name' => $user->name,
        'borrow_at' => now()->subHour(),
        'return_at' => now(),
    ]);

    Livewire::test(BorrowHistory::class)
        ->set('status', 'active')
        ->assertSee('貸出中工具')
        ->assertDontSee('返却済工具')
        ->set('status', 'returned')
        ->assertSee('返却済工具')
        ->assertDontSee('貸出中工具')
        ->set('status', 'all')
        ->set('search', 'H-100002')
        ->assertSee('貸出中工具')
        ->assertDontSee('返却済工具');
});
