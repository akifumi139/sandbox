@php
    $calendarLayout = $this->calendarLayout;
    $calendarDays = $this->calendarDays;
    $ganttDays = $this->ganttDays;
    $ganttStart = $ganttDays->first();
    $ganttEnd = $ganttDays->last();
    $month = $this->month();
@endphp

<div class="min-h-screen bg-zinc-50 p-4 text-zinc-800 antialiased sm:p-6" x-data="calendarTaskManager({ rowHeights: @js($calendarLayout['rowHeights']), ganttCellWidth: 48 })">
    <div class="mx-auto max-w-7xl space-y-5">
        <header class="flex flex-col justify-between gap-4 border-b border-zinc-200 pb-5 sm:flex-row sm:items-end">
            <div>
                <flux:heading size="xl" level="1">タスク管理</flux:heading>
                <flux:subheading>
                    {{ $viewMode === 'gantt' ? $this->centerDate()->format('Y年n月j日') . 'を中心に表示' : $month->format('Y年n月') . 'の月間スケジュール' }}
                </flux:subheading>
            </div>
            <div class="flex flex-wrap gap-2">
                <flux:button variant="primary" icon="plus"
                    x-on:click="$dispatch('open-task-form', { date: '{{ now()->toDateString() }}' })">
                    タスクを追加
                </flux:button>
                <div class="flex rounded-md border border-zinc-200 bg-white p-1">
                    <flux:button wire:click="$set('viewMode', 'gantt')" :variant="$viewMode === 'gantt' ? 'primary' : 'ghost'" size="sm"
                        icon="list-bullet">ガント</flux:button>
                    <flux:button wire:click="$set('viewMode', 'calendar')" :variant="$viewMode === 'calendar' ? 'primary' : 'ghost'" size="sm"
                        icon="calendar-days">月間</flux:button>
                </div>
            </div>
        </header>

        @if ($viewMode === 'gantt')
            <section class="overflow-hidden border border-zinc-200 bg-white shadow-sm" aria-label="ガントチャート">
                <div class="flex items-center justify-between border-b border-zinc-200 p-3">
                    <div class="flex items-center gap-1">
                        <flux:button wire:click="previousGanttRange" variant="ghost" size="sm" icon="chevron-left"
                            aria-label="前の期間" />
                        <flux:button wire:click="today" size="sm">今日</flux:button>
                        <flux:button wire:click="nextGanttRange" variant="ghost" size="sm" icon="chevron-right"
                            aria-label="次の期間" />
                    </div>
                    <span class="text-xs tabular-nums text-zinc-500">{{ $this->ganttTasks->count() }}件</span>
                </div>

                <div class="flex overflow-hidden">
                    <div class="z-10 w-40 shrink-0 border-e border-zinc-200 bg-zinc-50 sm:w-56">
                        <div
                            class="flex h-11 items-center border-b border-zinc-200 px-3 text-xs font-semibold text-zinc-500">
                            タスク名</div>
                        @forelse ($this->ganttTasks as $task)
                            <button type="button"
                                class="flex h-14 w-full items-center px-3 text-left text-sm font-medium hover:bg-zinc-100"
                                wire:key="gantt-name-{{ $task->id }}"
                                x-on:click="$dispatch('open-task-form', { taskId: {{ $task->id }} })">
                                <span class="truncate">{{ $task->name }}</span>
                            </button>
                        @empty
                            <div class="p-3 text-xs text-zinc-500">表示中のタスクはありません。</div>
                        @endforelse
                    </div>

                    <div class="min-w-0 flex-1 overflow-x-auto" x-ref="ganttScroller">
                        <div class="relative min-w-max" x-ref="ganttGrid"
                            style="width: {{ $ganttDays->count() * 48 }}px">
                            <div
                                class="flex h-11 border-b border-zinc-200 bg-zinc-50 text-center text-[11px] font-semibold text-zinc-500">
                                @foreach ($ganttDays as $day)
                                    <div class="flex w-12 shrink-0 flex-col items-center justify-center border-e border-zinc-100 {{ $day->isToday() ? 'bg-emerald-50 text-emerald-700' : ($day->isWeekend() ? 'bg-zinc-100/70' : '') }}"
                                        data-date="{{ $day->toDateString() }}"
                                        wire:key="gantt-day-{{ $day->toDateString() }}">
                                        <span>{{ $day->format('n/j') }}</span>
                                        <span
                                            class="text-[10px] font-normal">{{ ['日', '月', '火', '水', '木', '金', '土'][$day->dayOfWeek] }}</span>
                                    </div>
                                @endforeach
                            </div>

                            @foreach ($this->ganttTasks as $task)
                                @php
                                    $visibleStart = $task->start_date->lessThan($ganttStart)
                                        ? $ganttStart
                                        : $task->start_date;
                                    $visibleEnd = $task->end_date->greaterThan($ganttEnd) ? $ganttEnd : $task->end_date;
                                    $offset = $ganttStart->diffInDays($visibleStart);
                                    $duration = $visibleStart->diffInDays($visibleEnd) + 1;
                                @endphp
                                <div class="relative h-14 border-b border-zinc-100"
                                    wire:key="gantt-row-{{ $task->id }}">
                                    <div class="pointer-events-none absolute inset-0 flex">
                                        @foreach ($ganttDays as $day)
                                            <div
                                                class="w-12 shrink-0 border-e border-zinc-100 {{ $day->isWeekend() ? 'bg-zinc-50' : '' }}">
                                            </div>
                                        @endforeach
                                    </div>
                                    <button type="button" data-gantt-bar data-task-id="{{ $task->id }}"
                                        class="absolute top-3 flex h-8 items-center gap-2 overflow-hidden px-2 text-left text-xs font-medium text-white shadow-sm {{ $task->user_id === auth()->id() ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer' }}"
                                        style="background-color: {{ $task->color }}; left: {{ $offset * 48 }}px; width: {{ $duration * 48 }}px"
                                        x-on:pointerdown="startGantt($event, {{ $task->id }}, 'move')"
                                        x-on:click="if (!ignoreClick()) $dispatch('open-task-form', { taskId: {{ $task->id }} })">
                                        @if ($task->user_id === auth()->id())
                                            <span data-resize-edge="left"
                                                class="absolute inset-y-0 left-0 w-2 cursor-ew-resize"
                                                x-on:pointerdown.stop="startGantt($event, {{ $task->id }}, 'left')"></span>
                                            <span data-resize-edge="right"
                                                class="absolute inset-y-0 right-0 w-2 cursor-ew-resize"
                                                x-on:pointerdown.stop="startGantt($event, {{ $task->id }}, 'right')"></span>
                                        @endif
                                        <span class="truncate">{{ $task->name }}</span>
                                        <span
                                            class="ml-auto shrink-0 text-[10px] opacity-80">{{ $task->start_date->format('n/j') }}-{{ $task->end_date->format('n/j') }}</span>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        @else
            <section class="overflow-hidden border border-zinc-200 bg-white shadow-sm" aria-label="月間カレンダー">
                <div class="flex items-center justify-between border-b border-zinc-200 p-3">
                    <div class="flex items-center gap-1">
                        <flux:button wire:click="previousMonth" variant="ghost" size="sm" icon="chevron-left"
                            aria-label="前月" />
                        <span
                            class="min-w-28 text-center text-sm font-semibold tabular-nums">{{ $month->format('Y年n月') }}</span>
                        <flux:button wire:click="nextMonth" variant="ghost" size="sm" icon="chevron-right"
                            aria-label="翌月" />
                    </div>
                    <flux:button wire:click="thisMonth" size="sm">今月</flux:button>
                </div>

                <div class="overflow-x-auto">
                    <div class="min-w-200">
                        <div
                            class="grid grid-cols-7 border-b border-zinc-200 bg-zinc-50 text-center text-xs font-bold text-zinc-500">
                            @foreach (['日', '月', '火', '水', '木', '金', '土'] as $index => $weekday)
                                <div
                                    class="py-2.5 {{ $index === 0 ? 'text-rose-600' : ($index === 6 ? 'text-sky-600' : '') }}">
                                    {{ $weekday }}</div>
                            @endforeach
                        </div>

                        <div class="relative" x-ref="calendarGrid">
                            <div class="grid grid-cols-7 gap-px bg-zinc-200/70"
                                style="grid-template-rows: {{ implode('px ', $calendarLayout['rowHeights']) }}px">
                                @foreach ($calendarDays as $day)
                                    <button type="button"
                                        class="relative z-0 min-h-32 bg-white p-1.5 text-left transition hover:bg-zinc-50 {{ $day->month !== $month->month ? 'bg-zinc-50 text-zinc-400' : '' }}"
                                        data-date="{{ $day->toDateString() }}"
                                        wire:key="calendar-day-{{ $day->toDateString() }}"
                                        x-on:click="$dispatch('open-task-form', { date: '{{ $day->toDateString() }}' })">
                                    </button>
                                @endforeach
                            </div>

                            <div class="pointer-events-none absolute inset-0 z-20 grid grid-cols-7 gap-px"
                                style="grid-template-rows: {{ implode('px ', $calendarLayout['rowHeights']) }}px">
                                @foreach ($calendarDays as $day)
                                    <div class="flex items-start justify-between p-2">
                                        <span
                                            class="inline-flex size-6 items-center justify-center text-xs font-semibold {{ $day->isToday() ? 'rounded-full bg-emerald-600 text-white' : ($day->dayOfWeek === 0 ? 'text-rose-600' : ($day->dayOfWeek === 6 ? 'text-sky-600' : '')) }}">{{ $day->day }}</span>
                                        @if ($day->isToday())
                                            <span class="text-[10px] font-semibold text-emerald-700">今日</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="pointer-events-none absolute inset-0 z-10">
                                @php $rowOffsets = [0]; @endphp
                                @foreach ($calendarLayout['rowHeights'] as $height)
                                    @php $rowOffsets[] = end($rowOffsets) + $height + 1; @endphp
                                @endforeach
                                @foreach ($calendarLayout['bars'] as $bar)
                                    <button type="button" data-calendar-bar data-task-id="{{ $bar['id'] }}"
                                        data-row="{{ $bar['row'] }}"
                                        class="pointer-events-auto absolute flex h-6 items-center overflow-hidden rounded-sm px-2 text-left text-xs font-medium text-white shadow-sm {{ $bar['isOwner'] ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer' }}"
                                        style="background-color: {{ $bar['color'] }}; top: {{ $rowOffsets[$bar['row']] + 36 + $bar['lane'] * 28 }}px; left: calc({{ $bar['column'] }} * (100% + 1px) / 7); width: calc({{ $bar['span'] }} * (100% + 1px) / 7 - 1px)"
                                        wire:key="calendar-bar-{{ $bar['id'] }}-{{ $bar['row'] }}-{{ $bar['column'] }}"
                                        x-on:pointerdown="startCalendar($event, {{ $bar['id'] }}, {{ $bar['isOwner'] ? 'true' : 'false' }})"
                                        x-on:click.stop="if (!ignoreClick()) $dispatch('open-task-form', { taskId: {{ $bar['id'] }} })">
                                        <span class="truncate">{{ $bar['name'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </div>

    <livewire:calendar::task-form />
</div>
