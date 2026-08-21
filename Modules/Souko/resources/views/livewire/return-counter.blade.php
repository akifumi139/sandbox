<div class="max-w-7xl mx-auto md:space-y-6">
    <x-souko::header />

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="hidden md:block">
            <flux:heading size="xl" level="1">返却カウンター</flux:heading>
            <flux:subheading>現在貸し出されている工具を確認して、返却を完了します</flux:subheading>
        </div>

        <flux:tabs variant="segmented">
            <flux:tab name="board" href="{{ route('souko.rental-counter') }}">貸出</flux:tab>
            <flux:tab name="list" selected href="{{ route('souko.return-counter') }}">返却</flux:tab>
        </flux:tabs>
    </div>

    @if (session()->has('message'))
        <flux:badge color="green" size="lg" class="w-full justify-start p-3">
            {{ session('message') }}
        </flux:badge>
    @endif

    <flux:card class="p-0 overflow-hidden">
        <div class="p-4 sm:px-6 border-b border-zinc-200 flex items-center justify-between gap-3">
            <div>
                <flux:heading size="lg">借りている工具一覧</flux:heading>
                <flux:subheading>{{ $this->borrowedTools->total() }}件</flux:subheading>
            </div>

            <div class="flex items-center gap-2">
                <flux:button variant="ghost" size="sm" wire:click="toggleSelectAll">
                    {{ count($selectedToolIds) === $this->borrowedTools->count() && $this->borrowedTools->count() > 0 ? '選択解除' : 'すべて選択' }}
                </flux:button>
                <flux:button variant="primary" size="sm" wire:click="bulkReturn" :disabled="count($selectedToolIds) === 0">
                    一括返却 ({{ count($selectedToolIds) }})
                </flux:button>
            </div>
        </div>

        <flux:table class="px-3">
            <flux:table.columns>
                <flux:table.column class="w-12">
                    <input type="checkbox" class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                        wire:click="toggleSelectAll" @checked(count($selectedToolIds) === $this->borrowedTools->count() && $this->borrowedTools->count() > 0)>
                </flux:table.column>
                <flux:table.column>管理番号</flux:table.column>
                <flux:table.column>工具名</flux:table.column>
                <flux:table.column>使用者</flux:table.column>
                <flux:table.column>貸出日時</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($this->borrowedTools as $log)
                    @php
                        $tool = $log->tool;
                        $borrowerName = trim((string) ($log->user_name ?: $log->user?->name ?? '-'));
                    @endphp

                    <flux:table.row wire:key="borrowed-tool-{{ $tool?->id ?? $log->id }}">
                        <flux:table.cell>
                            <input type="checkbox"
                                class="h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500"
                                value="{{ $tool?->id ?? 0 }}" wire:model.live="selectedToolIds"
                                @checked(in_array($tool?->id ?? 0, $selectedToolIds, true))>
                        </flux:table.cell>
                        <flux:table.cell class="font-mono text-xs">
                            {{ $tool?->management_number ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $tool?->name ?? '削除された工具' }}
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="font-medium">{{ $borrowerName }}</div>
                        </flux:table.cell>
                        <flux:table.cell variant="dim">
                            {{ $log->logged_at->format('Y/m/d H:i') }}
                        </flux:table.cell>
                        <flux:table.cell class="text-right">
                            <flux:button variant="primary" size="sm"
                                wire:click="returnTool({{ $tool?->id ?? 0 }})">
                                返却
                            </flux:button>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="6" class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                            借りている工具はありません
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="p-4 border-t border-zinc-200 dark:border-zinc-700">
            {{ $this->borrowedTools->links() }}
        </div>
    </flux:card>
</div>
