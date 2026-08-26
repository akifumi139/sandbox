<?php

namespace Modules\Syako\Livewire;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Syako\Models\Booking;
use Modules\Syako\Models\Vehicle;

class VehicleBookingMonthly extends Component
{
    #[Url(as: 'month', history: true)]
    public string $currentMonth = '';

    #[Url(as: 'vehicle', history: true)]
    public string $selectedVehicle = '';

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

    public function updatedSelectedVehicle(): void
    {
        $this->clearCalendarData();
    }

    #[On('booking-saved')]
    #[On('booking-deleted')]
    public function clearCalendarData(): void
    {
        unset($this->vehicles, $this->calendarDays, $this->bookingsByDate);
    }

    /** @return Collection<int, Vehicle> */
    #[Computed]
    public function vehicles(): Collection
    {
        return Vehicle::query()->where('is_active', true)->orderBy('name')->get();
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

        $bookings = Booking::query()
            ->with(['vehicle', 'user'])
            ->where('starts_at', '<', $calendarDays->last()->endOfDay())
            ->where('ends_at', '>', $calendarDays->first()->startOfDay())
            ->when($this->selectedVehicle !== '', fn ($query) => $query->where('vehicle_id', $this->selectedVehicle))
            ->orderBy('starts_at')
            ->get();

        return $calendarDays->mapWithKeys(function (CarbonImmutable $day) use ($bookings): array {
            return [$day->toDateString() => $bookings->filter(
                fn (Booking $booking): bool => $booking->starts_at->lessThan($day->endOfDay())
                    && $booking->ends_at->greaterThan($day->startOfDay())
            )];
        });
    }

    public function month(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->currentMonth.'-01');
    }

    public function render(): View
    {
        return view('syako::livewire.vehicle-booking-monthly');
    }
}
