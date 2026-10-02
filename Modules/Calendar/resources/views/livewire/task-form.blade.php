<flux:modal wire:model.self="showModal" class="md:w-2xl">
    <form wire:submit="save" class="space-y-6">
        <flux:heading size="lg">
            {{ $taskId === null ? '新規タスク' : ($readOnly ? 'タスク詳細' : 'タスク編集') }}
        </flux:heading>
        <div>
            <flux:input wire:model="form.name" placeholder="タスク名を入力..." maxlength="255" autofocus :disabled="$readOnly"
                class="text-lg font-medium" />
            <flux:error name="form.name" />
        </div>

        <flux:textarea wire:model="form.assignees" label="担当者（1行に1名）" rows="3" placeholder="例：大野&#10;佐藤"
            :disabled="$readOnly" />

        <section class="space-y-4">
            <flux:radio.group wire:model.live="form.schedule_type" variant="cards" class="grid-cols-2 max-sm:flex-col">
                <flux:radio value="all_day" :disabled="$readOnly">
                    <flux:radio.indicator />
                    <div class="flex-1">
                        <flux:heading class="leading-4">終日</flux:heading>
                        <flux:text size="sm" class="mt-2">時間を指定しないイベント</flux:text>
                    </div>
                </flux:radio>

                <flux:radio value="timed" :disabled="$readOnly">
                    <flux:radio.indicator />
                    <div class="flex-1">
                        <flux:heading class="leading-4">時間指定</flux:heading>
                        <flux:text size="sm" class="mt-2">開始・終了時刻を設定</flux:text>
                    </div>
                </flux:radio>
            </flux:radio.group>

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.start_date" type="date" label="開始日" :disabled="$readOnly" />
                <flux:input wire:model="form.end_date" type="date" label="終了日" :disabled="$readOnly" />
            </div>

            @if ($form['schedule_type'] === 'timed')
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="form.start_time" type="time" label="開始時刻" :disabled="$readOnly" />
                    <flux:input wire:model="form.end_time" type="time" label="終了時刻" :disabled="$readOnly" />
                </div>
            @endif
        </section>

        <flux:color-picker wire:model="form.color" label="表示色" format="hex" :swatches="['#10B981', '#0EA5E9', '#F59E0B', '#F43F5E', '#8B5CF6', '#64748B']" :disabled="$readOnly" />

        <div class="flex items-center gap-2 border-t border-zinc-200 pt-5 dark:border-zinc-700">
            @if ($taskId !== null && !$readOnly)
                <flux:button type="button" variant="subtle" icon="trash" class="text-red-600 hover:text-red-700"
                    wire:click="delete" wire:confirm="このタスクを削除しますか？">
                    削除
                </flux:button>
            @endif

            <flux:spacer />

            <flux:modal.close>
                <flux:button variant="ghost">
                    {{ $readOnly ? '閉じる' : 'キャンセル' }}
                </flux:button>
            </flux:modal.close>

            @unless ($readOnly)
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled"
                    wire:target="save">
                    {{ $taskId === null ? 'タスクを作成' : '保存' }}
                </flux:button>
            @endunless
        </div>
    </form>
</flux:modal>
