<?php

use Livewire\Livewire;
use Modules\Souko\Database\Seeders\ToolSeeder;
use Modules\Souko\Livewire\Inventory;
use Modules\Souko\Models\Tool;

it('shows seeded tools in the inventory table', function (): void {
    (new ToolSeeder)->run();

    Livewire::test(Inventory::class)
        ->assertSee('T-000123')
        ->assertSee('インパクトドライバー')
        ->assertSee('電動工具')
        ->assertSee('TD172DRGX')
        ->assertSee('T-000456')
        ->assertSee('T-000789')
        ->assertSee('L-000001')
        ->assertSee('L-000002');
});

it('filters inventory tools by search text', function (): void {
    (new ToolSeeder)->run();

    Livewire::test(Inventory::class)
        ->set('search', '丸ノコ')
        ->assertSee('T-000789')
        ->assertDontSee('T-000123')
        ->assertDontSee('T-000456');
});

it('filters inventory tools by type', function (): void {
    (new ToolSeeder)->run();

    Livewire::test(Inventory::class)
        ->set('type', '脚立')
        ->assertSee('L-000001')
        ->assertSee('L-000002')
        ->assertSee('脚立')
        ->assertDontSee('T-000123')
        ->assertDontSee('T-000456')
        ->assertDontSee('T-000789');
});

it('keeps unique management numbers for tools of the same type', function (): void {
    Tool::factory()->ladder()->create([
        'management_number' => 'L-900001',
        'model' => '6尺',
    ]);
    Tool::factory()->ladder()->create([
        'management_number' => 'L-900002',
        'model' => '6尺',
    ]);

    Livewire::test(Inventory::class)
        ->set('type', '脚立')
        ->assertSee('L-900001')
        ->assertSee('L-900002')
        ->assertSee('6尺');

    expect(Tool::query()->where('type', '脚立')->count())->toBe(2)
        ->and(Tool::query()->where('management_number', 'L-900001')->exists())->toBeTrue()
        ->and(Tool::query()->where('management_number', 'L-900002')->exists())->toBeTrue();
});

it('adds a tool to the inventory', function (): void {
    Livewire::test(Inventory::class)
        ->set('form.management_number', 'T-900001')
        ->set('form.name', 'テスト工具')
        ->set('form.type', '脚立')
        ->set('form.model', 'TEST-001')
        ->set('form.manufacturer', 'テストメーカー')
        ->set('form.status', 'available')
        ->call('saveTool')
        ->assertHasNoErrors()
        ->assertSee('テスト工具')
        ->assertSee('脚立')
        ->assertSee('T-900001');

    expect(Tool::query()->where('management_number', 'T-900001')->where('type', '脚立')->exists())->toBeTrue();
});

it('deletes a tool from the inventory', function (): void {
    $tool = Tool::query()->create([
        'management_number' => 'T-900002',
        'name' => '削除対象工具',
        'model' => 'DELETE-001',
        'manufacturer' => 'テストメーカー',
        'status' => 'available',
    ]);

    Livewire::test(Inventory::class)
        ->call('deleteTool', $tool->id)
        ->assertSee('削除対象工具 を削除しました。');

    expect(Tool::query()->whereKey($tool->id)->exists())->toBeFalse();
});
