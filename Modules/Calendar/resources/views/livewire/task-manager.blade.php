@php
    $month = $this->month();
    $centerDate = $this->centerDate();
    $weekdayNames = ['日', '月', '火', '水', '木', '金', '土'];
    $monthDays = $viewMode === 'month' ? $this->monthDays : collect();
    $monthTasks = $viewMode === 'month' ? $this->monthTasks : collect();
    $weekDays = $viewMode === 'week' ? $this->weekDays : collect();
    $weekTasks = $viewMode === 'week' ? $this->weekTasks : collect();
    $dayTasks = $viewMode === 'day' ? $this->dayTasks : collect();
    $daySchedule = $viewMode === 'day' ? $this->daySchedule : [];
    $periodLabel = match ($viewMode) {
        'week' => $weekDays->first()->format('n/j') . ' - ' . $weekDays->last()->format('n/j'),
        'day' => $centerDate->format('Y年n月j日') . '（' . $weekdayNames[$centerDate->dayOfWeek] . '）',
        default => $month->format('Y年n月'),
    };
@endphp

<div class="min-h-screen bg-[#f7f6f2] px-4 py-6 text-[#2c3440] antialiased sm:px-6 lg:px-8" x-data="calendarTaskManager({})">
    <div class="mx-auto max-w-[1600px]">
        <header class="mb-7 flex flex-wrap items-end justify-between gap-5 border-b border-[#e4e7e5] pb-5">
            <div>
                <flux:heading size="xl" level="1" class="mt-2">タスク管理</flux:heading>
                <flux:subheading class="mt-1">{{ $periodLabel }}</flux:subheading>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <flux:button variant="primary" icon="plus"
                    x-on:click="$dispatch('open-task-form', { date: '{{ now()->toDateString() }}' })">
                    タスクを追加
                </flux:button>
                <div class="flex rounded-lg bg-[#e8efeb] p-1" role="group" aria-label="表示単位">
                    <flux:button wire:click="$set('viewMode', 'month')" :variant="$viewMode === 'month' ? 'primary' : 'ghost'" size="sm">月</flux:button>
                    <flux:button wire:click="$set('viewMode', 'week')" :variant="$viewMode === 'week' ? 'primary' : 'ghost'" size="sm">週</flux:button>
                    <flux:button wire:click="$set('viewMode', 'day')" :variant="$viewMode === 'day' ? 'primary' : 'ghost'" size="sm">日</flux:button>
                </div>
            </div>
        </header>

        <section class="overflow-hidden rounded-lg border border-[#e1e6e2] bg-white shadow-sm" aria-label="タスクスケジュール">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#e8ece9] px-4 py-3 md:px-5">
                <div class="flex items-center gap-2">
                    <flux:button wire:click="previousPeriod" variant="ghost" size="sm" icon="chevron-left"
                        aria-label="前の期間" />
                    <flux:button wire:click="today" size="sm">今日</flux:button>
                    <flux:button wire:click="nextPeriod" variant="ghost" size="sm" icon="chevron-right"
                        aria-label="次の期間" />
                    <span class="ml-1 text-sm font-bold tabular-nums text-[#46514b]">{{ $periodLabel }}</span>
                </div>
                <span class="text-xs text-[#89958f]">
                    {{ match ($viewMode) {'week' => $weekTasks->count(),'day' => $dayTasks->count(),default => $monthTasks->count()} }}件を表示
                </span>
            </div>

            @if ($viewMode === 'month')
                <div class="overflow-x-auto overscroll-x-contain" x-ref="monthScroller">
                    <div class="min-w-max" x-ref="monthGrid"
                        style="--label-width: 250px; width: {{ 250 + $monthDays->count() * 42 }}px">
                        <div class="sticky top-0 z-10 grid h-12 border-b border-[#e8ece9] bg-[#f8faf8] text-center text-[10px] font-semibold text-[#76837d]"
                            style="grid-template-columns: 250px repeat({{ $monthDays->count() }}, 42px)">
                            <div
                                class="sticky left-0 z-20 flex items-center border-e border-[#e3e8e4] bg-[#f8faf8] px-4 text-left">
                                タスク / 担当</div>
                            @foreach ($monthDays as $day)
                                <button type="button" data-grid-day data-date="{{ $day->toDateString() }}"
                                    wire:key="month-day-{{ $day->toDateString() }}"
                                    wire:click="showDay('{{ $day->toDateString() }}')"
                                    aria-label="{{ $day->format('n月j日') }}を日表示"
                                    class="flex flex-col items-center justify-center border-e border-[#edf0ed] hover:bg-[#eaf2ed] {{ $day->isToday() ? 'bg-[#eaf2ed] text-[#416b63]' : ($day->isWeekend() ? 'bg-[#fafaf7]' : '') }}">
                                    <span>{{ $day->day }}</span>
                                    <span class="font-normal">{{ $weekdayNames[$day->dayOfWeek] }}</span>
                                </button>
                            @endforeach
                        </div>

                        @forelse ($monthTasks as $task)
                            @php
                                $monthStart = $monthDays->first();
                                $monthEnd = $monthDays->last();
                                $visibleStart = $task->start_date->lessThan($monthStart)
                                    ? $monthStart
                                    : $task->start_date;
                                $visibleEnd = $task->end_date->greaterThan($monthEnd) ? $monthEnd : $task->end_date;
                                $offset = (int) $monthStart->diffInDays($visibleStart);
                                $duration = (int) $visibleStart->diffInDays($visibleEnd) + 1;
                            @endphp
                            <div class="relative grid h-14 border-b border-[#edf0ed]"
                                wire:key="month-row-{{ $task->id }}"
                                style="grid-template-columns: 250px repeat({{ $monthDays->count() }}, 42px)">
                                <div
                                    class="sticky left-0 z-4 flex min-w-0 items-center gap-2 border-e border-[#e3e8e4] bg-white px-3">
                                    <span class="size-2.5 shrink-0 rounded-full"
                                        style="background-color: {{ $task->color }}"></span>
                                    <div class="min-w-0">
                                        <div class="truncate text-xs font-semibold text-[#35413a]">{{ $task->name }}
                                        </div>
                                        <div class="truncate text-[10px] text-[#8a9690]">
                                            {{ implode('、', $task->assignees ?? []) }}</div>
                                    </div>
                                </div>
                                @foreach ($monthDays as $day)
                                    <div wire:key="month-cell-{{ $task->id }}-{{ $day->toDateString() }}"
                                        class="border-e border-[#edf0ed] {{ $day->isWeekend() ? 'bg-[#fafaf7]' : '' }} {{ $day->isToday() ? 'bg-[#eaf2ed]' : '' }}">
                                    </div>
                                @endforeach
                                <button type="button" data-month-bar data-task-id="{{ $task->id }}"
                                    class="absolute top-2 z-5 flex h-10 items-center gap-2 overflow-hidden rounded-sm px-2 text-left text-[11px] font-semibold text-[#35413a] shadow-sm {{ $task->user_id === auth()->id() ? 'cursor-grab active:cursor-grabbing' : 'cursor-pointer' }}"
                                    style="grid-column: {{ $offset + 2 }} / span {{ $duration }}; left: 3px; right: 3px; background-color: color-mix(in srgb, {{ $task->color }} 20%, white); border-inline-start: 3px solid {{ $task->color }}"
                                    x-on:pointerdown="startMonthDrag($event, {{ $task->id }}, 'move')"
                                    x-on:click="if (!ignoreClick()) $dispatch('open-task-form', { taskId: {{ $task->id }} })">
                                    @if ($task->user_id === auth()->id())
                                        <span data-resize-edge="left"
                                            class="absolute inset-y-0 left-0 w-2 cursor-ew-resize"
                                            x-on:pointerdown.stop="startMonthDrag($event, {{ $task->id }}, 'left')"></span>
                                        <span data-resize-edge="right"
                                            class="absolute inset-y-0 right-0 w-2 cursor-ew-resize"
                                            x-on:pointerdown.stop="startMonthDrag($event, {{ $task->id }}, 'right')"></span>
                                    @endif
                                    <span class="truncate">{{ $task->name }}</span>
                                    @if ($task->timeLabel() !== null)
                                        <span
                                            class="ml-auto shrink-0 text-[10px] font-medium opacity-80">{{ $task->timeLabel() }}</span>
                                    @endif
                                </button>
                            </div>
                        @empty
                            <div class="px-5 py-12 text-center text-sm text-[#87938d]">この月に表示するタスクはありません。</div>
                        @endforelse
                    </div>
                </div>
            @elseif ($viewMode === 'week')
                <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach ($weekDays as $day)
                        @php
                            $tasksForDay = $weekTasks->filter(
                                fn($task) => $task->start_date->lessThanOrEqualTo($day) &&
                                    $task->end_date->greaterThanOrEqualTo($day),
                            );
                            $isWeekend = $day->isWeekend();
                        @endphp
                        <section
                            class="min-h-82.5 rounded-lg p-3 {{ $isWeekend ? 'bg-[#f8f8f4]' : 'bg-[#f4f6f2]' }} {{ $day->isToday() ? 'ring-1 ring-[#9bb9aa]' : '' }}"
                            wire:key="week-column-{{ $day->toDateString() }}">
                            <header class="mb-3 flex items-center justify-between border-b border-[#e4e9e3] px-1 pb-3">
                                <span
                                    class="text-xs font-bold {{ $day->dayOfWeek === 0 ? 'text-rose-600' : ($day->dayOfWeek === 6 ? 'text-sky-600' : 'text-[#718078]') }}">
                                    {{ $day->format('n/j') }}　{{ $weekdayNames[$day->dayOfWeek] }}曜日
                                </span>
                                <button type="button" wire:click="showDay('{{ $day->toDateString() }}')"
                                    class="grid size-8 place-items-center rounded-full text-sm font-bold {{ $day->isToday() ? 'bg-[#405d65] text-white' : 'text-[#53655d] hover:bg-white' }}"
                                    aria-label="{{ $day->format('n月j日') }}を日表示">{{ $day->day }}</button>
                            </header>
                            <div class="space-y-2">
                                @forelse ($tasksForDay as $task)
                                    <button type="button"
                                        wire:key="week-task-{{ $task->id }}-{{ $day->toDateString() }}"
                                        class="w-full rounded-md border border-[#e5e9e3] bg-white p-3 text-left shadow-sm hover:border-[#b7c9bf]"
                                        x-on:click="$dispatch('open-task-form', { taskId: {{ $task->id }} })">
                                        <span class="mb-2 block h-1 w-8 rounded-full"
                                            style="background-color: {{ $task->color }}"></span>
                                        <span
                                            class="block text-[13px] font-bold leading-5 text-[#35413a]">{{ $task->name }}</span>
                                        <span class="mt-2 block text-[11px] font-semibold text-[#567d73]">
                                            @if ($task->timeLabel() === null)
                                                終日
                                            @else
                                                {{ ($task->start_date->lessThan($day) ? '00:00' : substr($task->start_time, 0, 5)) . ' - ' . ($task->end_date->greaterThan($day) ? '24:00' : substr($task->end_time, 0, 5)) }}
                                                @if ($task->start_date->lessThan($day) || $task->end_date->greaterThan($day))
                                                    · 継続
                                                @endif
                                            @endif
                                        </span>
                                        @if (count($task->assignees ?? []))
                                            <span class="mt-2 block truncate text-[11px] text-[#87938d]">担当
                                                {{ implode('、', $task->assignees) }}</span>
                                        @endif
                                    </button>
                                @empty
                                    <div
                                        class="rounded-md border border-dashed border-[#dbe3dc] px-3 py-6 text-center text-xs text-[#9aa49e]">
                                        予定はありません</div>
                                @endforelse
                            </div>
                            <button type="button"
                                class="mt-3 w-full rounded-md border border-dashed border-[#cddad2] p-2.5 text-xs font-semibold text-[#6a8c82] hover:bg-[#edf4ef]"
                                x-on:click="$dispatch('open-task-form', { date: '{{ $day->toDateString() }}' })">＋
                                この日に追加</button>
                        </section>
                    @endforeach
                </div>
            @else
                @php
                    $allDayTasks = $dayTasks->filter(
                        fn($task) => $task->start_time === null || $task->end_time === null,
                    );
                @endphp
                <div class="grid grid-cols-[72px_minmax(480px,1fr)] border-b border-[#e8ece9]">
                    <div class="bg-[#f8faf8] px-3 py-4 text-xs font-bold text-[#76837d]">終日</div>
                    <div class="flex min-h-14 flex-wrap gap-2 p-2">
                        @forelse ($allDayTasks as $task)
                            <button type="button" wire:key="all-day-task-{{ $task->id }}"
                                class="flex max-w-full flex-col items-start rounded-md px-3 py-2 text-left text-xs font-semibold text-[#35413a]"
                                style="background-color: color-mix(in srgb, {{ $task->color }} 18%, white); border-inline-start: 3px solid {{ $task->color }}"
                                x-on:click="$dispatch('open-task-form', { taskId: {{ $task->id }} })">
                                <span class="max-w-full truncate">{{ $task->name }}</span>
                                @if (count($task->assignees ?? []))
                                    <span class="mt-1 max-w-full truncate text-[10px] font-medium text-[#718078]">
                                        {{ '担当 ' . implode('、', $task->assignees) }}
                                    </span>
                                @endif
                            </button>
                        @empty
                            <span class="py-2 text-xs text-[#a0aaa4]">終日の予定はありません</span>
                        @endforelse
                    </div>
                </div>
                <div class="max-h-180 overflow-y-auto">
                    <div class="grid grid-cols-[72px_minmax(480px,1fr)]">
                        <div>
                            @foreach (range(0, 23) as $hour)
                                <div
                                    class="h-13 border-b border-[#edf0ed] px-2.5 py-1 text-right text-[11px] text-[#84908f]">
                                    {{ sprintf('%02d:00', $hour) }}</div>
                            @endforeach
                        </div>
                        <div class="relative" style="min-height: 1248px">
                            @foreach (range(0, 23) as $hour)
                                <div class="h-13 border-b border-[#edf0ed]"></div>
                            @endforeach
                            @foreach ($daySchedule as $scheduledTask)
                                @php
                                    $task = $scheduledTask['task'];
                                    $duration = max(30, $scheduledTask['end'] - $scheduledTask['start']);
                                    $top = ($scheduledTask['start'] / 60) * 52 + 3;
                                    $height = max(34, ($duration / 60) * 52 - 6);
                                    $left = ($scheduledTask['lane'] * 100) / $scheduledTask['laneCount'];
                                    $width = 100 / $scheduledTask['laneCount'];
                                    $startLabel = sprintf(
                                        '%02d:%02d',
                                        intdiv($scheduledTask['start'], 60),
                                        $scheduledTask['start'] % 60,
                                    );
                                    $endLabel =
                                        $scheduledTask['end'] === 1440
                                            ? '24:00'
                                            : sprintf(
                                                '%02d:%02d',
                                                intdiv($scheduledTask['end'], 60),
                                                $scheduledTask['end'] % 60,
                                            );
                                @endphp
                                <button type="button" wire:key="timed-task-{{ $task->id }}"
                                    class="absolute z-2 flex items-center gap-2 overflow-hidden rounded-md px-2.5 py-1.5 text-left text-xs font-bold text-[#35413a]"
                                    style="top: {{ $top }}px; height: {{ $height }}px; left: {{ $left }}%; width: {{ $width }}%; background-color: color-mix(in srgb, {{ $task->color }} 18%, white); border-inline-start: 3px solid {{ $task->color }}"
                                    x-on:click="$dispatch('open-task-form', { taskId: {{ $task->id }} })">
                                    <span
                                        class="shrink-0 text-[10px] font-medium">{{ $startLabel . ' - ' . $endLabel }}</span>
                                    <span class="min-w-0 flex-1 truncate">{{ $task->name }}</span>
                                    @if (count($task->assignees ?? []))
                                        <span class="max-w-[40%] shrink-0 truncate text-[10px] font-medium">
                                            {{ '担当 ' . implode('、', $task->assignees) }}
                                        </span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </div>

    <livewire:calendar::task-form />
</div>
