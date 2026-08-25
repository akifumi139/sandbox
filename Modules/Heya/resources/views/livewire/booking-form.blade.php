<flux:modal wire:model="showModal" class="md:w-xl">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $bookingId === null ? '新規予約' : ($readOnly ? '予約詳細' : '予約を編集') }}
            </flux:heading>
            <flux:text class="mt-2">会議室と利用時間を指定してください。</flux:text>
        </div>

        <flux:select wire:model="form.room_id" label="会議室" :disabled="$readOnly">
            <flux:select.option value="">会議室を選択</flux:select.option>
            @foreach ($rooms as $room)
                <flux:select.option :value="$room->id" wire:key="booking-room-{{ $room->id }}">
                    {{ $room->name }}
                </flux:select.option>
            @endforeach
        </flux:select>

        <flux:input wire:model="form.date" type="date" label="日付" :disabled="$readOnly" />

        <div class="grid grid-cols-2 gap-4">
            <flux:input wire:model="form.start_time" type="time" min="07:00" max="17:30" step="1800"
                label="開始" :disabled="$readOnly" />
            <flux:input wire:model="form.end_time" type="time" min="07:30" max="18:00" step="1800"
                label="終了" :disabled="$readOnly" />
        </div>

        <div class="flex items-center justify-between gap-3">
            @if ($bookingId !== null && !$readOnly)
                <flux:button type="button" variant="danger" icon="trash" wire:click="delete"
                    wire:confirm="この予約を削除しますか？">
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
