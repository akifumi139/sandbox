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
        ->assertSee('TD172DRGX')
        ->assertSee('T-000456')
        ->assertSee('T-000789');
});

it('filters inventory tools by search text', function (): void {
    (new ToolSeeder)->run();

    Livewire::test(Inventory::class)
        ->set('search', '丸ノコ')
        ->assertSee('T-000789')
        ->assertDontSee('T-000123')
        ->assertDontSee('T-000456');
});

it('adds a tool to the inventory', function (): void {
    Livewire::test(Inventory::class)
        ->set('form.management_number', 'T-900001')
        ->set('form.name', 'テスト工具')
        ->set('form.model', 'TEST-001')
        ->set('form.manufacturer', 'テストメーカー')
        ->set('form.status', 'available')
        ->call('saveTool')
        ->assertHasNoErrors()
        ->assertSee('テスト工具')
        ->assertSee('T-900001');

    expect(Tool::query()->where('management_number', 'T-900001')->exists())->toBeTrue();
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
        ->assertDontSee('削除対象工具')
        ->assertDatabaseMissing('souko__tools', ['id' => $tool->id]);
});
