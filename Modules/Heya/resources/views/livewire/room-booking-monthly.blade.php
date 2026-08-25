<div class="min-h-screen p-4 text-zinc-800 antialiased sm:p-6">
    @php $month = $this->month(); @endphp

    <div class="mx-auto max-w-6xl space-y-5">
        <header class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <div>
                <flux:heading size="xl" level="1">予約状況</flux:heading>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button :href="route('heya.daily', ['date' => now()->toDateString()])" icon="list-bullet" wire:navigate>日表示</flux:button>
                <flux:button :href="route('heya.rooms')" icon="cog-6-tooth" wire:navigate>会議室管理</flux:button>
                <flux:button variant="primary" icon="plus"
                    x-on:click="$dispatch('open-booking-form', { date: '{{ now()->toDateString() }}' })">新規予約
                </flux:button>
            </div>
        </header>

        <div class="overflow-hidden border border-zinc-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-zinc-200 p-3">
                <div class="flex items-center gap-2">
                    <flux:button wire:click="previousMonth" variant="ghost" size="sm" icon="chevron-left"
                        aria-label="前月" />
                    <input type="month" wire:model.change="currentMonth" aria-label="表示する月"
                        class="block h-10 w-40 rounded-md border border-zinc-200 bg-zinc-50 px-3 text-sm text-zinc-800 outline-none transition focus:border-zinc-900 focus:bg-white disabled:bg-zinc-100 disabled:text-zinc-400" />
                    <flux:button wire:click="nextMonth" variant="ghost" size="sm" icon="chevron-right"
                        aria-label="翌月" />
                </div>
                <flux:button wire:click="thisMonth" size="sm">今月</flux:button>
            </div>

            <div class="overflow-x-auto">
                <div class="min-w-200">
                    <div
                        class="grid grid-cols-7 border-b border-zinc-200 bg-zinc-50 text-center text-xs font-bold text-zinc-500">
                        @foreach (['日', '月', '火', '水', '木', '金', '土'] as $weekday)
                            <div class="py-2.5 first:text-rose-600 last:text-sky-600">{{ $weekday }}</div>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-7 bg-zinc-200/70 gap-px">
                        @foreach ($this->calendarDays as $day)
                            @php
                                $isCurrentMonth = $day->month === $month->month;
                                $isToday = $day->isToday();
                                $dayBookings = $this->bookingsByDate->get($day->toDateString(), collect());
                            @endphp
                            <a href="{{ route('heya.daily', ['date' => $day->format('Y-m-d')]) }}"
                                class="min-h-32 bg-white p-1.5 {{ $isCurrentMonth ? '' : 'bg-zinc-50 text-zinc-400' }}"
                                wire:key="month-day-{{ $day->toDateString() }}">
                                <button type="button" class="flex w-full items-center justify-between p-1 text-left"
                                    x-on:click="$dispatch('open-booking-form', { roomId: {{ 'null' }}, date: '{{ $day->toDateString() }}', startTime: '09:00' })">
                                    <span
                                        class="inline-flex size-6 items-center justify-center text-xs font-semibold {{ $isToday ? 'rounded-full bg-emerald-600 text-white' : ($day->dayOfWeek === 0 ? 'text-rose-600' : ($day->dayOfWeek === 6 ? 'text-sky-600' : '')) }}">{{ $day->day }}</span>
                                    @if ($isToday)
                                        <span class="text-[10px] font-semibold text-emerald-700">今日</span>
                                    @endif
                                </button>

                                <div class="mt-1 space-y-1">
                                    @foreach ($dayBookings->take(3) as $booking)
                                        @php $isOwner = $booking->user_id === auth()->id(); @endphp
                                        <button type="button"
                                            class="block w-full border px-1.5 py-1 text-left text-[11px] leading-tight {{ $isOwner ? 'border-emerald-200 bg-emerald-50 text-emerald-950' : 'border-zinc-200 bg-zinc-100 text-zinc-800' }}"
                                            wire:key="monthly-booking-{{ $booking->id }}"
                                            x-on:click.stop="$dispatch('open-booking-form', { bookingId: {{ $booking->id }} })">
                                            <span
                                                class="block truncate font-bold">{{ $booking->starts_at->format('H:i') }}
                                                {{ $booking->room->name }}</span>
                                            <span
                                                class="block truncate text-[10px] opacity-70">{{ $booking->user->name }}</span>
                                        </button>
                                    @endforeach
                                    @if ($dayBookings->count() > 3)
                                        <p class="px-1 text-[10px] text-zinc-500">ほか {{ $dayBookings->count() - 3 }}件
                                        </p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    <livewire:heya::booking-form />
</div>
