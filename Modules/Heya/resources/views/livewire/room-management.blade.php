<div class="min-h-screen bg-zinc-50 p-4 text-zinc-800 antialiased sm:p-6">
    <div class="mx-auto max-w-6xl space-y-6">
        <header class="flex flex-col justify-between gap-4 border-b border-zinc-200 pb-5 sm:flex-row sm:items-end">
            <div>
                <flux:heading size="xl" level="1">会議室管理</flux:heading>
                <flux:subheading>予約で利用できる会議室を管理します。</flux:subheading>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('heya.monthly')" icon="calendar-days" wire:navigate>予約へ戻る</flux:button>
                <flux:button wire:click="create" variant="primary" icon="plus">会議室を登録</flux:button>
            </div>
        </header>

        <section class="grid grid-cols-3 divide-x divide-zinc-200 border-y border-zinc-200 bg-white"
            aria-label="会議室の集計">
            <div class="px-4 py-3 sm:px-5">
                <p class="text-xs font-medium text-zinc-500">登録</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-zinc-950">{{ $rooms->count() }}</p>
            </div>
            <div class="px-4 py-3 sm:px-5">
                <p class="text-xs font-medium text-zinc-500">利用可能</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">
                    {{ $rooms->where('is_active', true)->count() }}</p>
            </div>
            <div class="px-4 py-3 sm:px-5">
                <p class="text-xs font-medium text-zinc-500">停止中</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-zinc-500">
                    {{ $rooms->where('is_active', false)->count() }}</p>
            </div>
        </section>

        <section class="overflow-hidden border border-zinc-200 bg-white shadow-sm" aria-labelledby="room-list-heading">
            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-4 py-3 sm:px-5">
                <div>
                    <flux:heading id="room-list-heading" size="lg">会議室一覧</flux:heading>
                    <flux:subheading>無効にした会議室は新しい予約で選択できません。</flux:subheading>
                </div>
                <span class="shrink-0 text-xs tabular-nums text-zinc-500">{{ $rooms->count() }}件</span>
            </div>

            <div class="overflow-x-auto mx-auto max-w-7xl">
                <flux:table class="min-w-180">
                    <flux:table.columns>
                        <flux:table.column class="ps-3!">会議室</flux:table.column>
                        <flux:table.column>予約数</flux:table.column>
                        <flux:table.column>状態</flux:table.column>
                        <flux:table.column align="end">操作</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($rooms as $room)
                            <flux:table.row :key="$room->id"
                                class="{{ $room->is_active ? '' : 'bg-zinc-50 text-zinc-500' }}">
                                <flux:table.cell>
                                    <div class="flex items-center gap-3 ps-3!">
                                        <span
                                            class="size-2 rounded-full {{ $room->is_active ? 'bg-emerald-500' : 'bg-zinc-300' }}"
                                            aria-hidden="true"></span>
                                        <span
                                            class="font-medium {{ $room->is_active ? 'text-zinc-950' : 'text-zinc-600' }}">{{ $room->name }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap tabular-nums">{{ $room->bookings_count }}件
                                </flux:table.cell>
                                <flux:table.cell class="py-0">
                                    <flux:badge size="sm" :color="$room->is_active ? 'green' : 'zinc'" inset="top bottom">
                                        {{ $room->is_active ? '利用可能' : '停止中' }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="py-0">
                                    <div class="flex justify-end gap-1">
                                        <flux:tooltip content="会議室名を編集">
                                            <flux:button wire:click="edit({{ $room->id }})" size="sm"
                                                variant="ghost" icon="pencil-square"
                                                aria-label="{{ $room->name }}を編集" />
                                        </flux:tooltip>
                                        <flux:tooltip :content="$room->is_active ? '予約受付を停止' : '予約受付を再開'">
                                            <flux:button wire:click="toggleActive({{ $room->id }})" size="sm"
                                                variant="ghost" :icon="$room->is_active ? 'pause-circle' : 'play-circle'" :aria-label="$room->name . ($room->is_active ? 'を無効化' : 'を有効化')" />
                                        </flux:tooltip>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="py-14 text-center">
                                    <div class="flex flex-col items-center gap-3 text-zinc-500">
                                        <flux:icon.building-office-2 class="size-6" />
                                        <p class="text-sm">会議室が登録されていません。</p>
                                        <flux:button wire:click="create" size="sm" icon="plus">最初の会議室を登録
                                        </flux:button>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </section>
    </div>

    <flux:modal wire:model="showModal" class="md:w-lg">
        <form wire:submit="save" class="space-y-6">
            <div>
                <flux:heading size="lg">{{ $roomId === null ? '会議室を登録' : '会議室を編集' }}</flux:heading>
                <flux:text class="mt-2">予約画面に表示する名称を入力してください。</flux:text>
            </div>
            <flux:input wire:model="form.name" label="会議室名" placeholder="例: 会議室 A" maxlength="255" autofocus />
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">キャンセル</flux:button>
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled"
                    wire:target="save">保存</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
