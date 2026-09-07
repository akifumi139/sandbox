<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Souko\Livewire\RentalCounter;
use Modules\Souko\Models\Tool;
use Modules\Souko\Models\ToolLog;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    actingAs(User::factory()->create());
});

it('adds a tool to the cart when its qr code is scanned', function (): void {
    Tool::query()->create([
        'management_number' => 'T-900001',
        'name' => 'テスト工具',
        'type' => '脚立',
        'model' => 'TEST-1000',
        'manufacturer' => 'Test Corp',
        'status' => 'available',
    ]);

    Livewire::test(RentalCounter::class)
        ->call('addToolByQrCode', 'T-900001')
        ->assertSet('cart.0.code', 'T-900001')
        ->assertSet('cart.0.name', 'テスト工具')
        ->assertSet('cart.0.type', '脚立')
        ->assertSet('cart.0.quantity', 1)
        ->assertSet('scannerMessage', 'テスト工具 を追加しました。');
});

it('keeps the cart unique when the same qr code is scanned twice', function (): void {
    Tool::query()->create([
        'management_number' => 'T-900002',
        'name' => '数量確認工具',
        'type' => '脚立',
        'model' => 'TEST-2000',
        'manufacturer' => 'Test Corp',
        'status' => 'available',
    ]);

    $component = Livewire::test(RentalCounter::class)
        ->call('addToolByQrCode', 'T-900002')
        ->call('addToolByQrCode', 'T-900002');

    expect($component->get('cart'))->toHaveCount(1)
        ->and($component->get('cart.0.quantity'))->toBe(1);
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
    actingAs($user);

    $component = Livewire::test(RentalCounter::class)
        ->call('addToolByQrCode', 'T-900004')
        ->set('borrowerName', '山田 太郎')
        ->call('checkout');

    $component->assertSet('cart', [])
        ->assertSet('borrowerName', '');

    $tool = Tool::query()->where('management_number', 'T-900004')->firstOrFail();
    $log = ToolLog::query()->where('tool_id', $tool->id)->firstOrFail();

    expect($tool->status)->toBe('rented')
        ->and($log->user_id)->toBe($user->id)
        ->and($log->user_name)->toBe('山田 太郎')
        ->and($log->borrow_at)->not->toBeNull()
        ->and($log->return_at)->toBeNull();
});

it('creates only one borrow log even if the same tool is scanned more than once', function (): void {
    $user = User::factory()->create();
    actingAs($user);

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
        ->set('borrowerName', '鈴木 次郎')
        ->call('checkout');

    $tool = Tool::query()->where('management_number', 'T-900005')->firstOrFail();

    expect(ToolLog::query()->where('tool_id', $tool->id)->count())->toBe(1)
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
        ->assertSet('cart', []);
});
