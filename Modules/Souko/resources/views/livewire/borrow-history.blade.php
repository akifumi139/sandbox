<div class="max-w-7xl mx-auto space-y-6">

    <x-souko::header />

    <!-- Title & Header Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">貸出・返却履歴</flux:heading>
            <flux:subheading>工具の貸出および返却のログ履歴を確認・管理できます</flux:subheading>
        </div>
    </div>

    <!-- Flash Message -->
    @if (session()->has('message'))
        <flux:badge color="green" size="lg" class="w-full justify-start p-3">
            {{ session('message') }}
        </flux:badge>
    @endif

    <!-- Search & Filter -->
    <flux:card>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <flux:input wire:model.live.debounce.300ms="search" label="検索" placeholder="使用者名・工具名・管理番号"
                    icon="magnifying-glass" />
            </div>

            <div>
                <flux:select wire:model.live="status" label="種別">
                    <flux:select.option value="all">すべて</flux:select.option>
                    <flux:select.option value="borrow">貸出</flux:select.option>
                    <flux:select.option value="return">返却</flux:select.option>
                </flux:select>
            </div>
        </div>
    </flux:card>

    <!-- Borrow History Table Section -->
    <flux:card class="p-0 overflow-hidden">
        <div class="p-4 sm:px-6 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <div>
                <flux:heading size="lg">履歴ログ一覧</flux:heading>
                <flux:subheading>{{ $this->logs->total() }}件の記録</flux:subheading>
            </div>

            <flux:button variant="ghost" size="sm" icon="arrow-down-tray">
                CSV出力
            </flux:button>
        </div>

        <!-- Table -->
        <flux:table class="px-3">
            <flux:table.columns>
                <flux:table.column>日時</flux:table.column>
                <flux:table.column>種別</flux:table.column>
                <flux:table.column>管理番号 / 工具名</flux:table.column>
                <flux:table.column>管理者 / 使用者</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->logs as $log)
                    <flux:table.row wire:key="log-{{ $log->id }}">
                        <flux:table.cell variant="dim" class="whitespace-nowrap">
                            {{ $log->logged_at->format('Y/m/d H:i') }}
                        </flux:table.cell>

                        <flux:table.cell>
                            @php
                                $isBorrow = $log->action_type === 'borrow';
                                $badgeColor = $isBorrow ? 'amber' : 'green';
                                $actionLabel = $isBorrow ? '貸出' : '返却';
                            @endphp

                            <flux:badge :color="$badgeColor" size="sm" inset="top bottom">
                                {{ $actionLabel }}
                            </flux:badge>
                        </flux:table.cell>

                        <flux:table.cell>
                            <div class="font-mono text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $log->tool->management_number ?? '-' }}
                            </div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $log->tool->name ?? '削除された工具' }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="font-medium">
                            {{ $log->user->name ?? '-' }} / {{ $log->user_name }}
                        </flux:table.cell>
                        </flux:row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5" class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                                該当するログ履歴がありません
                            </flux:table.cell>
                        </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <!-- Pagination Container -->
        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
            <flux:pagination :paginator="$this->logs" />
        </div>
    </flux:card>
</div>
