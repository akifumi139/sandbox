<div class="min-h-screen  p-4 text-zinc-800 antialiased sm:p-6">
    @php
        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $displayDate = $this->date();
    @endphp

    <div class="mx-auto max-w-7xl space-y-5">
        <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <flux:heading size="xl" level="1">会議室予約</flux:heading>

                </flux:subheading>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('heya.monthly', ['month' => $displayDate->format('Y-m')])" icon="calendar-days" wire:navigate>月表示</flux:button>
                <flux:button :href="route('heya.rooms')" icon="cog-6-tooth" wire:navigate>会議室管理</flux:button>
                <flux:button variant="primary" icon="plus"
                    x-on:click="$dispatch('open-booking-form', { date: '{{ $selectedDate }}' })">新規予約</flux:button>
            </div>
        </header>

        <div
            class="flex flex-col justify-between gap-3 border-y border-zinc-200 bg-white px-3 py-2.5 sm:flex-row sm:items-center">
            <div class="flex items-center gap-2">
                <flux:button wire:click="previousDay" variant="ghost" size="sm" icon="chevron-left"
                    aria-label="前日" />
                <flux:date-picker wire:model.change="selectedDate" class="tabular-nums" aria-label="表示する日付" />
                <flux:button wire:click="nextDay" variant="ghost" size="sm" icon="chevron-right" aria-label="翌日" />
                <flux:button wire:click="today" size="sm">今日</flux:button>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-3 text-xs text-zinc-500">
                    <span class="flex items-center gap-1.5"><span
                            class="size-2.5 rounded-sm bg-emerald-500"></span>自分</span>
                    <span class="flex items-center gap-1.5"><span
                            class="size-2.5 rounded-sm bg-zinc-400"></span>他の予約</span>
                </div>
            </div>
        </div>

        <div class="overflow-hidden border border-zinc-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <div class="min-w-300">
                    <div
                        class="grid grid-cols-[180px_repeat(22,minmax(0,1fr))] border-b border-zinc-200 bg-zinc-50 text-xs font-semibold text-zinc-500">
                        <div class="border-r border-zinc-200 p-3.5">会議室</div>
                        @foreach (range(7, 17) as $hour)
                            <div class="col-span-2 border-r border-zinc-200 py-3 text-center last:border-r-0">
                                {{ sprintf('%02d:00', $hour) }}</div>
                        @endforeach
                    </div>

                    <div class="divide-y divide-zinc-200">
                        @forelse ($this->rooms as $room)
                            <div class="grid min-h-24 grid-cols-[180px_repeat(22,minmax(0,1fr))]"
                                wire:key="daily-room-{{ $room->id }}">
                                <div class="flex flex-col justify-center border-r border-zinc-200 bg-zinc-50/60 p-4">
                                    <p class="text-sm font-bold text-zinc-900">{{ $room->name }}</p>
                                </div>
                                <div class="col-span-22 grid grid-cols-22 p-2">
                                    @foreach (range(0, 21) as $slot)
                                        @php
                                            $slotTime = $displayDate
                                                ->setTime(7, 0)
                                                ->addMinutes($slot * 30)
                                                ->format('H:i');
                                        @endphp
                                        <button type="button"
                                            class="row-start-1 min-h-20 border-r border-zinc-100 hover:bg-emerald-50 focus-visible:z-20 focus-visible:outline-2 focus-visible:outline-emerald-600"
                                            aria-label="{{ $room->name }} {{ $slotTime }}から予約"
                                            x-on:click="$dispatch('open-booking-form', { roomId: {{ $room->id }}, date: '{{ $selectedDate }}', startTime: '{{ $slotTime }}' })"></button>
                                    @endforeach
                                    @foreach ($this->bookings->where('room_id', $room->id) as $booking)
                                        @php $isOwner = $booking->user_id === auth()->id(); @endphp
                                        <button type="button" wire:key="daily-booking-{{ $booking->id }}"
                                            style="grid-column: {{ $this->slotStart($booking) }} / span {{ $this->slotSpan($booking) }}"
                                            class="z-10 row-start-1 m-0.5 flex min-w-0 flex-col justify-between border p-2 text-left shadow-sm {{ $isOwner ? 'border-emerald-300 bg-emerald-50 text-emerald-950' : 'border-zinc-300 bg-zinc-100 text-zinc-800' }}"
                                            x-on:click.stop="$dispatch('open-booking-form', { bookingId: {{ $booking->id }} })">
                                            <span class="truncate text-xs font-bold">{{ $booking->user->name }}</span>
                                            <span class="mt-2 flex justify-between gap-2 text-[11px] opacity-75"><span
                                                    class="truncate">{{ $booking->room->name }}</span><span>{{ $booking->starts_at->format('H:i') }}–{{ $booking->ends_at->format('H:i') }}</span></span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="p-10 text-center text-sm text-zinc-500">利用可能な会議室がありません。</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
    <livewire:heya::booking-form />
</div>
