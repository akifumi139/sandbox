<flux:modal wire:model="showModal" class="md:w-xl">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $taskId === null ? '新規タスク' : ($readOnly ? 'タスク詳細' : 'タスクを編集') }}
            </flux:heading>
            <flux:text class="mt-2">タスク名と実施期間を入力してください。</flux:text>
        </div>

        <flux:input wire:model="form.name" label="タスク名" placeholder="例: 仕様書作成" maxlength="255" autofocus
            :disabled="$readOnly" />

        <div class="grid gap-4 sm:grid-cols-2">
            <flux:input wire:model="form.start_date" type="date" label="開始日" :disabled="$readOnly" />
            <flux:input wire:model="form.end_date" type="date" label="終了日" :disabled="$readOnly" />
        </div>

        <flux:color-picker wire:model="form.color" label="表示色" format="hex" :swatches="['#10B981', '#0EA5E9', '#F59E0B', '#F43F5E', '#8B5CF6', '#64748B']" :disabled="$readOnly" />

        <div class="flex items-center justify-between gap-3">
            @if ($taskId !== null && !$readOnly)
                <flux:button type="button" variant="danger" icon="trash" wire:click="delete"
                    wire:confirm="このタスクを削除しますか？">
                    削除
                </flux:button>
            @else
                <span></span>
            @endif

            <div class="flex gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">閉じる</flux:button>
                @unless ($readOnly)
                    <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled"
                        wire:target="save">
                        保存
                    </flux:button>
                @endunless
            </div>
        </div>
    </form>
</flux:modal>
