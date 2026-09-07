    <div class="max-w-7xl mx-auto space-y-6">

        <x-souko::header />

        @if (session('message'))
            <div
                class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
                {{ session('message') }}
            </div>
        @endif

        <!-- Title & Header Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <flux:heading size="xl" level="1">在庫管理</flux:heading>
                <flux:subheading>登録されている工具を管理します</flux:subheading>
            </div>

            <flux:modal.trigger name="tool-create-modal">
                <flux:button variant="primary" icon="plus">
                    工具を登録
                </flux:button>
            </flux:modal.trigger>
        </div>

        <flux:modal name="tool-create-modal" class="md:max-w-160">
            <form wire:submit.prevent="saveTool" class="space-y-6">
                <div>
                    <flux:heading size="lg">工具を登録</flux:heading>
                    <flux:text class="mt-2">新しい工具の管理情報を入力してください。</flux:text>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <flux:input wire:model="form.management_number" label="管理番号" placeholder="T-900001" />
                    <flux:input wire:model="form.name" label="工具名" placeholder="インパクトドライバー" />
                    <flux:input wire:model="form.type" label="種類" placeholder="脚立" />
                    <flux:input wire:model="form.model" label="型番" placeholder="TD172DRGX" />
                    <flux:input wire:model="form.manufacturer" label="メーカー" placeholder="Makita" />
                    <div class="md:col-span-2">
                        <flux:select wire:model="form.status" label="状態">
                            <flux:select.option value="available">貸出可能</flux:select.option>
                            <flux:select.option value="rented">貸出中</flux:select.option>
                            <flux:select.option value="maintenance">修理中</flux:select.option>
                            <flux:select.option value="disposed">廃棄</flux:select.option>
                        </flux:select>
                    </div>
                </div>

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button type="button" variant="ghost">キャンセル</flux:button>
                    </flux:modal.close>

                    <flux:button type="submit" variant="primary" icon="plus">登録する</flux:button>
                </div>
            </form>
        </flux:modal>

        <flux:modal wire:model="showQrModal" class="md:w-96">
            <div x-data="{
                managementNumber: @entangle('qrManagementNumber'),
                renderQr() {
                    const el = this.$refs.qrCanvas;
                    el.innerHTML = '';
            
                    if (!this.managementNumber) {
                        return;
                    }
            
                    QRCode.toCanvas(el, this.managementNumber, {
                        width: 220,
                        margin: 1,
                        color: {
                            dark: '#111827',
                            light: '#ffffff'
                        }
                    }, (error) => {
                        if (error) {
                            console.error(error);
                        }
                    });
                }
            }" x-init="renderQr()"
                x-effect="if ($wire.showQrModal) { $nextTick(() => renderQr()); }">
                <div class="space-y-5 text-center">
                    <div>
                        <flux:heading size="lg">QRコード表示</flux:heading>
                        <flux:text class="mt-2">この工具の管理番号をQRで表示します。</flux:text>
                    </div>

                    <div
                        class="flex justify-center rounded-2xl border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-950">
                        <canvas x-ref="qrCanvas" class="max-w-full"></canvas>
                    </div>

                    <p class="font-mono text-sm text-zinc-600 dark:text-zinc-300" x-text="managementNumber"></p>

                    <flux:button type="button" variant="primary" class="w-full" x-on:click="$wire.showQrModal = false">
                        閉じる
                    </flux:button>
                </div>
            </div>
        </flux:modal>

        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <flux:card class="space-y-1">
                <flux:subheading size="sm">登録工具</flux:subheading>
                <div class="flex items-baseline gap-1">
                    <span class="text-3xl font-bold tracking-tight">{{ $this->tools->total() }}</span>
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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <flux:input wire:model.live="search" label="検索" placeholder="工具名・種類・管理番号・型番"
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

                <div>
                    <flux:select wire:model.live="type" label="種類">
                        <flux:select.option value="">すべて</flux:select.option>
                        @foreach ($this->toolTypes as $toolType)
                            <flux:select.option value="{{ $toolType }}">{{ $toolType }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>
            </div>
        </flux:card>

        <!-- Inventory Table Section -->
        <flux:card class="p-0 overflow-hidden">
            <div class="p-4 sm:px-6 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <div>
                    <flux:heading size="lg">工具一覧</flux:heading>
                    <flux:subheading>
                        {{ $this->tools->total() }}件
                        @if (count($selectedToolIds) > 0)
                            / {{ count($selectedToolIds) }}件選択中
                        @endif
                    </flux:subheading>
                </div>

                <flux:button wire:click="exportSelectedToolQrCodes" wire:loading.attr="disabled"
                    wire:target="exportSelectedToolQrCodes" variant="primary" size="sm" icon="arrow-down-tray"
                    :disabled="count($selectedToolIds) === 0">
                    <span wire:loading.remove wire:target="exportSelectedToolQrCodes">QRをPDF出力</span>
                    <span wire:loading wire:target="exportSelectedToolQrCodes">PDF生成中...</span>
                </flux:button>
            </div>

            @error('selectedToolIds')
                <div
                    class="border-b border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-800 dark:bg-rose-950 dark:text-rose-200">
                    {{ $message }}
                </div>
            @enderror

            <!-- Table (Desktop & Mobile Responsive) -->
            <flux:table class="px-3">
                <flux:table.columns>
                    <flux:table.column>
                        <input type="checkbox" aria-label="表示中の工具をすべて選択" wire:click="toggleSelectAllVisibleTools"
                            @checked(
                                $this->tools->isNotEmpty() &&
                                    count(array_diff(
                                            $this->tools->pluck('id')->map(fn($id) => (int) $id)->all(),
                                            array_map('intval', $selectedToolIds))) === 0)
                            class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800">
                    </flux:table.column>
                    <flux:table.column>管理番号</flux:table.column>
                    <flux:table.column>工具名</flux:table.column>
                    <flux:table.column>種類</flux:table.column>
                    <flux:table.column>型番</flux:table.column>
                    <flux:table.column>状態</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @php
                        $previousToolType = null;
                    @endphp

                    @forelse ($this->tools as $tool)
                        @php
                            $toolTypeLabel = filled($tool->type) ? $tool->type : '未分類';
                        @endphp

                        @if ($previousToolType !== $toolTypeLabel)
                            <flux:table.row wire:key="tool-type-{{ $toolTypeLabel }}">
                                <flux:table.cell colspan="7"
                                    class="bg-zinc-50 py-2 text-xs font-semibold text-zinc-600 dark:bg-zinc-900 dark:text-zinc-300">
                                    {{ $toolTypeLabel }}
                                </flux:table.cell>
                            </flux:table.row>
                            @php
                                $previousToolType = $toolTypeLabel;
                            @endphp
                        @endif

                        <flux:table.row wire:key="tool-{{ $tool->id }}">
                            <flux:table.cell>
                                <input type="checkbox" value="{{ $tool->id }}" wire:model.live="selectedToolIds"
                                    aria-label="{{ $tool->management_number }}を選択"
                                    class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800">
                            </flux:table.cell>
                            <flux:table.cell class="font-mono text-xs">{{ $tool->management_number }}
                            </flux:table.cell>
                            <flux:table.cell class="font-medium">{{ $tool->name }}</flux:table.cell>
                            <flux:table.cell variant="dim">{{ $toolTypeLabel }}</flux:table.cell>
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
                                        <flux:menu.item wire:click="openQrModal('{{ $tool->management_number }}')"
                                            icon="qr-code">
                                            QRコード表示
                                        </flux:menu.item>
                                        <flux:menu.separator />
                                        <flux:menu.item wire:click="deleteTool({{ $tool->id }})" variant="danger"
                                            icon="trash">
                                            削除
                                        </flux:menu.item>
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                                該当する工具がありません
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <!-- Pagination Container -->
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
                {{ $this->tools->links() }}
            </div>
        </flux:card>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.0.0/build/qrcode.min.js"></script>
