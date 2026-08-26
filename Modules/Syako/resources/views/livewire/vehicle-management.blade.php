<div class="min-h-screen bg-zinc-50 p-4 text-zinc-800 antialiased sm:p-6">
    <div class="mx-auto max-w-6xl space-y-6">
        <header class="flex flex-col justify-between gap-4 border-b border-zinc-200 pb-5 sm:flex-row sm:items-end">
            <div>
                <flux:heading size="xl" level="1">車両管理</flux:heading>
                <flux:subheading>予約で利用できる車両を管理します。</flux:subheading>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('syako.monthly')" icon="calendar-days" wire:navigate>予約へ戻る</flux:button>
                <flux:button wire:click="create" variant="primary" icon="plus">車両を登録</flux:button>
            </div>
        </header>

        <section class="grid grid-cols-3 divide-x divide-zinc-200 border-y border-zinc-200 bg-white" aria-label="車両の集計">
            <div class="px-4 py-3 sm:px-5">
                <p class="text-xs font-medium text-zinc-500">登録</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-zinc-950">{{ $vehicles->count() }}</p>
            </div>
            <div class="px-4 py-3 sm:px-5">
                <p class="text-xs font-medium text-zinc-500">利用可能</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-emerald-700">
                    {{ $vehicles->where('is_active', true)->count() }}</p>
            </div>
            <div class="px-4 py-3 sm:px-5">
                <p class="text-xs font-medium text-zinc-500">停止中</p>
                <p class="mt-1 text-2xl font-semibold tabular-nums text-zinc-500">
                    {{ $vehicles->where('is_active', false)->count() }}</p>
            </div>
        </section>

        <section class="overflow-hidden border border-zinc-200 bg-white shadow-sm" aria-labelledby="room-list-heading">
            <div class="flex items-center justify-between gap-4 border-b border-zinc-200 px-4 py-3 sm:px-5">
                <div>
                    <flux:heading id="room-list-heading" size="lg">車両一覧</flux:heading>
                    <flux:subheading>無効にした車両は新しい予約で選択できません。</flux:subheading>
                </div>
                <span class="shrink-0 text-xs tabular-nums text-zinc-500">{{ $vehicles->count() }}件</span>
            </div>

            <div class="overflow-x-auto mx-auto max-w-7xl">
                <flux:table class="min-w-180 mx-auto max-w-7xl">
                    <flux:table.columns>
                        <flux:table.column class="ps-3!">車両</flux:table.column>
                        <flux:table.column>予約数</flux:table.column>
                        <flux:table.column>状態</flux:table.column>
                        <flux:table.column align="end">操作</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @forelse ($vehicles as $vehicle)
                            <flux:table.row :key="$vehicle->id"
                                class="{{ $vehicle->is_active ? '' : 'bg-zinc-50 text-zinc-500' }}">
                                <flux:table.cell>
                                    <div class="flex items-center gap-3 ps-3!">
                                        <span class="size-2 rounded-full {{ $vehicle->is_active ? '' : 'bg-zinc-300' }}"
                                            style="{{ $vehicle->is_active ? 'background-color: ' . $vehicle->color_code . ';' : '' }}"
                                            aria-hidden="true"></span>
                                        <span
                                            class="font-medium {{ $vehicle->is_active ? 'text-zinc-950' : 'text-zinc-600' }}">{{ $vehicle->name }}</span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap tabular-nums">{{ $vehicle->bookings_count }}件
                                </flux:table.cell>
                                <flux:table.cell class="py-0">
                                    <flux:badge size="sm" :color="$vehicle->is_active ? 'green' : 'zinc'" inset="top bottom">
                                        {{ $vehicle->is_active ? '利用可能' : '停止中' }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell class="py-0">
                                    <div class="flex justify-end gap-1">
                                        <flux:tooltip content="車両名を編集">
                                            <flux:button wire:click="edit({{ $vehicle->id }})" size="sm"
                                                variant="ghost" icon="pencil-square"
                                                aria-label="{{ $vehicle->name }}を編集" />
                                        </flux:tooltip>
                                        <flux:tooltip :content="$vehicle->is_active ? '予約受付を停止' : '予約受付を再開'">
                                            <flux:button wire:click="toggleActive({{ $vehicle->id }})" size="sm"
                                                variant="ghost" :icon="$vehicle->is_active ? 'pause-circle' : 'play-circle'" :aria-label="$vehicle->name .
                                                    ($vehicle->is_active ? 'を無効化' : 'を有効化')" />
                                        </flux:tooltip>
                                        <flux:tooltip content="車両を削除">
                                            <flux:button wire:click="delete({{ $vehicle->id }})" size="sm"
                                                variant="ghost" icon="trash" aria-label="{{ $vehicle->name }}を削除"
                                                wire:confirm="この車両を削除しますか？" />
                                        </flux:tooltip>
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="py-14 text-center">
                                    <div class="flex flex-col items-center gap-3 text-zinc-500">
                                        <flux:icon.building-office-2 class="size-6" />
                                        <p class="text-sm">車両が登録されていません。</p>
                                        <flux:button wire:click="create" size="sm" icon="plus">最初の車両を登録
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
                <flux:heading size="lg">{{ $vehicleId === null ? '車両を登録' : '車両を編集' }}</flux:heading>
                <flux:text class="mt-2">車両情報を入力してください。</flux:text>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="form.name" label="車両名" placeholder="例: 車両 A" maxlength="255" autofocus />
                <flux:input wire:model="form.color_code" type="color" label="表示色" />
                <flux:input wire:model="form.vehicle_number" label="車両番号" placeholder="例: 広島 500 あ 1001"
                    maxlength="255" />
                <flux:input wire:model="form.manufacturer" label="メーカー" placeholder="例: トヨタ" maxlength="255" />
                <flux:input wire:model="form.model" label="車種" placeholder="例: プリウス" maxlength="255" />
                <flux:input wire:model="form.model_code" label="型式" placeholder="例: ZVW50" maxlength="255" />
            </div>
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showModal', false)">キャンセル</flux:button>
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled"
                    wire:target="save">保存</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
