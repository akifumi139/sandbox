<?php

use Livewire\Livewire;
use Modules\Souko\Database\Seeders\ToolSeeder;
use Modules\Souko\Livewire\Inventory;

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
