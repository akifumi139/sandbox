<flux:modal wire:model="showModal" class="md:w-xl">
    <form wire:submit="save" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ $bookingId === null ? '新規予約' : ($readOnly ? '予約詳細' : '予約を編集') }}
            </flux:heading>
            <flux:text class="mt-2">車両と利用時間を指定してください。</flux:text>
        </div>

        <flux:select wire:model="form.vehicle_id" label="車両" :disabled="$readOnly">
            <flux:select.option value="">車両を選択</flux:select.option>
            @foreach ($vehicles as $vehicle)
                <flux:select.option :value="$vehicle->id" wire:key="booking-vehicle-{{ $vehicle->id }}">
                    {{ $vehicle->name }}
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

        <flux:textarea wire:model="form.notes" label="備考" rows="3" :disabled="$readOnly" />

        <div class="space-y-3">
            <flux:heading size="sm">貸出品</flux:heading>
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:checkbox wire:model="form.has_fuel_card" label="給油カード" :disabled="$readOnly" />
                <flux:checkbox wire:model="form.has_etc_card" label="ETCカード" :disabled="$readOnly" />
            </div>
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
