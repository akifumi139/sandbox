<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Souko\Livewire\RentalCounter;
use Modules\Souko\Models\Tool;
use Modules\Souko\Models\ToolLog;

it('adds a tool to the cart when its qr code is scanned', function (): void {
    Tool::query()->create([
        'management_number' => 'T-900001',
        'name' => 'テスト工具',
        'model' => 'TEST-1000',
        'manufacturer' => 'Test Corp',
        'status' => 'available',
    ]);

    Livewire::test(RentalCounter::class)
        ->call('addToolByQrCode', 'T-900001')
        ->assertSet('cart.3.code', 'T-900001')
        ->assertSet('cart.3.name', 'テスト工具')
        ->assertSet('cart.3.quantity', 1)
        ->assertSet('scannerMessage', 'テスト工具 を追加しました。');
});

it('increments the quantity when the same qr code is scanned twice', function (): void {
    Tool::query()->create([
        'management_number' => 'T-900002',
        'name' => '数量確認工具',
        'model' => 'TEST-2000',
        'manufacturer' => 'Test Corp',
        'status' => 'available',
    ]);

    Livewire::test(RentalCounter::class)
        ->call('addToolByQrCode', 'T-900002')
        ->call('addToolByQrCode', 'T-900002')
        ->assertSet('cart.3.quantity', 2);
});

it('creates a borrow log and marks the tool as rented when checkout is completed', function (): void {
    Tool::query()->create([
        'management_number' => 'T-900004',
        'name' => '貸出処理工具',
        'model' => 'TEST-4000',
        'manufacturer' => 'Test Corp',
        'status' => 'available',
    ]);

    $user = User::factory()->create();

    $component = Livewire::test(RentalCounter::class)
        ->actingAs($user)
        ->call('addToolByQrCode', 'T-900004')
        ->set('borrower_name', '山田 太郎')
        ->call('checkout');

    $component->assertSet('cart', [])
        ->assertSet('borrower_name', '');

    $tool = Tool::query()->where('management_number', 'T-900004')->firstOrFail();
    $log = ToolLog::query()->where('tool_id', $tool->id)->where('action_type', 'borrow')->firstOrFail();

    expect($tool->status)->toBe('rented')
        ->and($log->user_id)->toBe($user->id)
        ->and($log->user_name)->toBe('山田 太郎');
});

it('creates only one borrow log even if the same tool is scanned more than once', function (): void {
    Tool::query()->create([
        'management_number' => 'T-900005',
        'name' => '重複スキャン工具',
        'model' => 'TEST-5000',
        'manufacturer' => 'Test Corp',
        'status' => 'available',
    ]);

    Livewire::test(RentalCounter::class)
        ->call('addToolByQrCode', 'T-900005')
        ->call('addToolByQrCode', 'T-900005')
        ->set('borrower_name', '鈴木 次郎')
        ->call('checkout');

    $tool = Tool::query()->where('management_number', 'T-900005')->firstOrFail();

    expect(ToolLog::query()->where('tool_id', $tool->id)->where('action_type', 'borrow')->count())->toBe(1)
        ->and($tool->fresh()->status)->toBe('rented');
});

it('does not add tools that are not available', function (): void {
    Tool::query()->create([
        'management_number' => 'T-900003',
        'name' => '貸出不可工具',
        'model' => 'TEST-3000',
        'manufacturer' => 'Test Corp',
        'status' => 'rented',
    ]);

    Livewire::test(RentalCounter::class)
        ->call('addToolByQrCode', 'T-900003')
        ->assertSee('貸出不可工具 は貸し出しできません。')
        ->assertDontSee('T-900003');
});
