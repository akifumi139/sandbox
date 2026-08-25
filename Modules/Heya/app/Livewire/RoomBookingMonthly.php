<?php

namespace Modules\Heya\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Heya\Models\Booking;
use Modules\Heya\Models\Room;

class RoomBookingMonthly extends Component
{
    #[Url(as: 'month', history: true)]
    public string $currentMonth = '';

    #[Url(as: 'room', history: true)]
    public string $selectedRoom = '';

    public function mount(): void
    {
        $this->currentMonth = $this->currentMonth !== ''
            ? CarbonImmutable::parse($this->currentMonth.'-01')->format('Y-m')
            : now()->format('Y-m');
    }

    public function previousMonth(): void
    {
        $this->currentMonth = $this->month()->subMonth()->format('Y-m');
        $this->clearCalendarData();
    }

    public function nextMonth(): void
    {
        $this->currentMonth = $this->month()->addMonth()->format('Y-m');
        $this->clearCalendarData();
    }

    public function thisMonth(): void
    {
        $this->currentMonth = now()->format('Y-m');
        $this->clearCalendarData();
    }

    public function updatedCurrentMonth(): void
    {
        $this->clearCalendarData();
    }

    public function updatedSelectedRoom(): void
    {
        $this->clearCalendarData();
    }

    #[On('booking-saved')]
    #[On('booking-deleted')]
    public function clearCalendarData(): void
    {
        unset($this->rooms, $this->calendarDays, $this->bookingsByDate);
    }

    /** @return Collection<int, Room> */
    #[Computed]
    public function rooms(): Collection
    {
        return Room::query()->where('is_active', true)->orderBy('name')->get();
    }

    /** @return SupportCollection<int, CarbonImmutable> */
    #[Computed]
    public function calendarDays(): SupportCollection
    {
        $start = $this->month()->startOfMonth()->startOfWeek(CarbonImmutable::SUNDAY);

        return collect(range(0, 41))->map(fn (int $offset) => $start->addDays($offset));
    }

    /** @return SupportCollection<string, Collection<int, Booking>> */
    #[Computed]
    public function bookingsByDate(): SupportCollection
    {
        $calendarDays = $this->calendarDays();

        return Booking::query()
            ->with(['room', 'user'])
            ->whereBetween('starts_at', [$calendarDays->first()->startOfDay(), $calendarDays->last()->endOfDay()])
            ->when($this->selectedRoom !== '', fn ($query) => $query->where('room_id', $this->selectedRoom))
            ->orderBy('starts_at')
            ->get()
            ->groupBy(fn (Booking $booking) => $booking->starts_at->toDateString());
    }

    public function month(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->currentMonth.'-01');
    }

    public function render(): View
    {
        return view('heya::livewire.room-booking-monthly');
    }
}
