<?php

namespace Modules\Heya\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Heya\Models\Booking;
use Modules\Heya\Models\Room;

class RoomBookingDaily extends Component
{
    #[Url(as: 'date', history: true)]
    public string $selectedDate = '';

    #[Url(as: 'room', history: true)]
    public string $selectedRoom = '';

    public function mount(): void
    {
        $this->selectedDate = $this->selectedDate !== ''
            ? CarbonImmutable::parse($this->selectedDate)->toDateString()
            : now()->toDateString();
    }

    public function previousDay(): void
    {
        $this->selectedDate = $this->date()->subDay()->toDateString();
        $this->clearCalendarData();
    }

    public function nextDay(): void
    {
        $this->selectedDate = $this->date()->addDay()->toDateString();
        $this->clearCalendarData();
    }

    public function today(): void
    {
        $this->selectedDate = now()->toDateString();
        $this->clearCalendarData();
    }

    public function updatedSelectedDate(): void
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
        unset($this->allRooms, $this->rooms, $this->bookings);
    }

    /** @return Collection<int, Room> */
    #[Computed]
    public function allRooms(): Collection
    {
        return Room::query()->where('is_active', true)->orderBy('name')->get();
    }

    /** @return Collection<int, Room> */
    #[Computed]
    public function rooms(): Collection
    {
        return Room::query()
            ->where('is_active', true)
            ->when($this->selectedRoom !== '', fn ($query) => $query->whereKey($this->selectedRoom))
            ->orderBy('name')
            ->get();
    }

    /** @return Collection<int, Booking> */
    #[Computed]
    public function bookings(): Collection
    {
        return Booking::query()
            ->with(['room', 'user'])
            ->whereBetween('starts_at', [$this->date()->startOfDay(), $this->date()->endOfDay()])
            ->when($this->selectedRoom !== '', fn ($query) => $query->where('room_id', $this->selectedRoom))
            ->orderBy('starts_at')
            ->get();
    }

    public function date(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->selectedDate);
    }

    public function slotStart(Booking $booking): int
    {
        return (($booking->starts_at->hour - 7) * 2) + intdiv($booking->starts_at->minute, 30) + 1;
    }

    public function slotSpan(Booking $booking): int
    {
        return max(1, (int) ($booking->starts_at->diffInMinutes($booking->ends_at) / 30));
    }

    public function render(): View
    {
        return view('heya::livewire.room-booking-daily');
    }
}
