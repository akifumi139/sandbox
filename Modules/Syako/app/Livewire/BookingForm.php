<?php

namespace Modules\Syako\Livewire;

use App\Models\User;
use Carbon\CarbonImmutable;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Syako\Actions\SaveBooking;
use Modules\Syako\Models\Booking;
use Modules\Syako\Models\Vehicle;

class BookingForm extends Component
{
    public bool $showModal = false;

    public bool $readOnly = false;

    public ?int $bookingId = null;

    /** @var array{vehicle_id: int|string, date: string, start_time: string, end_time: string, notes: string, has_fuel_card: bool, has_etc_card: bool} */
    public array $form = [
        'vehicle_id' => '',
        'date' => '',
        'start_time' => '09:00',
        'end_time' => '09:30',
        'notes' => '',
        'has_fuel_card' => false,
        'has_etc_card' => false,
    ];

    #[On('open-booking-form')]
    public function open(
        ?int $bookingId = null,
        ?int $vehicleId = null,
        ?string $date = null,
        ?string $startTime = null,
    ): void {
        $this->resetValidation();
        $this->bookingId = $bookingId;
        $this->readOnly = false;

        if ($bookingId !== null) {
            $booking = Booking::query()->whereKey($bookingId)->firstOrFail();
            Gate::authorize('view', $booking);

            $this->readOnly = Gate::denies('update', $booking);
            $this->form = [
                'vehicle_id' => $booking->vehicle_id,
                'date' => $booking->starts_at->format('Y-m-d'),
                'start_time' => $booking->starts_at->format('H:i'),
                'end_time' => $booking->ends_at->format('H:i'),
                'notes' => $booking->notes ?? '',
                'has_fuel_card' => $booking->has_fuel_card,
                'has_etc_card' => $booking->has_etc_card,
            ];
        } else {
            Gate::authorize('create', Booking::class);

            $start = $startTime ?? '09:00';
            $this->form = [
                'vehicle_id' => $vehicleId ?? '',
                'date' => $date ?? now()->toDateString(),
                'start_time' => $start,
                'end_time' => CarbonImmutable::createFromFormat('H:i', $start)->addMinutes(30)->format('H:i'),
                'notes' => '',
                'has_fuel_card' => false,
                'has_etc_card' => false,
            ];
        }

        $this->showModal = true;
    }

    public function save(SaveBooking $saveBooking): void
    {
        $booking = $this->bookingId === null
            ? null
            : Booking::query()->whereKey($this->bookingId)->firstOrFail();

        Gate::authorize($booking === null ? 'create' : 'update', $booking ?? Booking::class);

        $validated = $this->validate();
        [$startsAt, $endsAt] = $this->validatedPeriod();
        $user = Auth::user();

        abort_unless($user instanceof User, 401);

        $saveBooking->handle(
            $user,
            Vehicle::query()->whereKey($validated['form']['vehicle_id'])->firstOrFail(),
            $startsAt,
            $endsAt,
            $booking,
            $validated['form']['notes'],
            $validated['form']['has_fuel_card'],
            $validated['form']['has_etc_card'],
        );

        $this->showModal = false;
        $this->dispatch('booking-saved');
        Flux::toast(variant: 'success', text: $booking === null ? '予約を登録しました。' : '予約を更新しました。');
    }

    public function delete(): void
    {
        $booking = Booking::query()->whereKey($this->bookingId)->firstOrFail();
        Gate::authorize('delete', $booking);
        $booking->delete();

        $this->showModal = false;
        $this->dispatch('booking-deleted');
        Flux::toast(variant: 'success', text: '予約を削除しました。');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return [
            'form.vehicle_id' => [
                'required',
                'integer',
                Rule::exists((new Vehicle)->getTable(), 'id')->where('is_active', true),
            ],
            'form.date' => ['required', 'date_format:Y-m-d'],
            'form.start_time' => ['required', 'date_format:H:i'],
            'form.end_time' => ['required', 'date_format:H:i'],
            'form.notes' => ['nullable', 'string', 'max:2000'],
            'form.has_fuel_card' => ['boolean'],
            'form.has_etc_card' => ['boolean'],
        ];
    }

    /**
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function validatedPeriod(): array
    {
        $startsAt = CarbonImmutable::createFromFormat('!Y-m-d H:i', $this->form['date'].' '.$this->form['start_time']);
        $endsAt = CarbonImmutable::createFromFormat('!Y-m-d H:i', $this->form['date'].' '.$this->form['end_time']);
        $messages = [];

        if ($startsAt->minute % 30 !== 0 || $endsAt->minute % 30 !== 0) {
            $messages['form.start_time'] = '時刻は30分単位で指定してください。';
        }

        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            $messages['form.end_time'] = '終了時刻は開始時刻より後にしてください。';
        }

        if ($startsAt->format('H:i') < '07:00' || $endsAt->format('H:i') > '18:00') {
            $messages['form.start_time'] = '予約時間は7:00から18:00までです。';
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }

        return [$startsAt, $endsAt];
    }

    public function render(): View
    {
        return view('syako::livewire.booking-form', [
            'vehicles' => Vehicle::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
