    <div class="max-w-7xl mx-auto space-y-6">

        <x-souko::header />

        <!-- Title & Header Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">在庫管理</flux:heading>
                <flux:subheading>登録されている工具を管理します</flux:subheading>
            </div>

            <flux:button variant="primary" icon="plus">
                工具を登録
            </flux:button>
        </div>

        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card class="space-y-1">
                <flux:subheading size="sm">登録工具</flux:subheading>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-bold tracking-tight">{{ $totalTools }}</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">点</span>
                </div>
            </flux:card>

            <flux:card class="space-y-1">
                <flux:subheading size="sm">貸出可能</flux:subheading>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-bold tracking-tight">{{ $availableTools }}</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">点</span>
                </div>
            </flux:card>

            <flux:card class="space-y-1">
                <flux:subheading size="sm">貸出中</flux:subheading>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-bold tracking-tight">{{ $rentedTools }}</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">点</span>
                </div>
            </flux:card>

            <flux:card class="space-y-1">
                <flux:subheading size="sm">故障・修理</flux:subheading>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-bold tracking-tight">{{ $maintenanceTools }}</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">点</span>
                </div>
            </flux:card>
        </div>

        <!-- Search & Filter -->
        <flux:card>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <flux:input wire:model.live="search" label="検索" placeholder="工具名・管理番号・型番"
                        icon="magnifying-glass" />
                </div>

                <div>
                    <flux:select wire:model.live="status" label="状態">
                        <flux:select.option value="">すべて</flux:select.option>
                        <flux:select.option value="available">貸出可能</flux:select.option>
                        <flux:select.option value="rented">貸出中</flux:select.option>
                        <flux:select.option value="maintenance">修理中</flux:select.option>
                        <flux:select.option value="disposed">廃棄</flux:select.option>
                    </flux:select>
                </div>
            </div>
        </flux:card>

        <!-- Inventory Table Section -->
        <flux:card class="p-0 overflow-hidden">
            <div class="p-4 sm:px-6 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <div>
                    <flux:heading size="lg">工具一覧</flux:heading>
                    <flux:subheading>{{ $totalTools }}件</flux:subheading>
                </div>

                <flux:button variant="ghost" size="sm" icon="arrow-down-tray">
                    CSV出力
                </flux:button>
            </div>

            <!-- Table (Desktop & Mobile Responsive) -->
            <flux:table class="px-3">
                <flux:table.columns>
                    <flux:table.column>管理番号</flux:table.column>
                    <flux:table.column>工具名</flux:table.column>
                    <flux:table.column>型番</flux:table.column>
                    <flux:table.column>状態</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($tools as $tool)
                        <flux:table.row wire:key="tool-{{ $tool->id }}">
                            <flux:table.cell class="font-mono text-xs">{{ $tool->management_number }}</flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $tool->name }}</flux:table.cell>
                            <flux:table.cell variant="dim">{{ $tool->model ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                @php
                                    $badgeColor = match ($tool->status) {
                                        'available' => 'green',
                                        'rented' => 'amber',
                                        'maintenance' => 'rose',
                                        'disposed' => 'zinc',
                                        default => 'zinc',
                                    };

                                    $statusLabel = match ($tool->status) {
                                        'available' => '貸出可能',
                                        'rented' => '貸出中',
                                        'maintenance' => '修理中',
                                        'disposed' => '廃棄',
                                        default => '未設定',
                                    };
                                @endphp

                                <flux:badge :color="$badgeColor" size="sm" inset="top bottom">{{ $statusLabel }}
                                </flux:badge>
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:dropdown>
                                    <flux:button variant="ghost" size="sm" icon="ellipsis-vertical"
                                        inset="top bottom" />
                                    <flux:menu>
                                        <flux:menu.item icon="pencil-square">編集</flux:menu.item>
                                        <flux:menu.item icon="arrow-right-start-on-rectangle">貸し出し</flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item variant="danger" icon="trash">削除</flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                                該当する工具がありません
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <!-- Pagination Container -->
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                {{-- <flux:pagination :paginator="$tools" /> --}}
            </div>
        </flux:card>
    </div>
