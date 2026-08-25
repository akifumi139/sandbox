<?php

namespace Modules\Heya\Livewire;

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
use Modules\Heya\Actions\SaveBooking;
use Modules\Heya\Models\Booking;
use Modules\Heya\Models\Room;

class BookingForm extends Component
{
    public bool $showModal = false;

    public bool $readOnly = false;

    public ?int $bookingId = null;

    /** @var array{room_id: int|string, date: string, start_time: string, end_time: string} */
    public array $form = [
        'room_id' => '',
        'date' => '',
        'start_time' => '09:00',
        'end_time' => '09:30',
    ];

    #[On('open-booking-form')]
    public function open(
        ?int $bookingId = null,
        ?int $roomId = null,
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
                'room_id' => $booking->room_id,
                'date' => $booking->starts_at->format('Y-m-d'),
                'start_time' => $booking->starts_at->format('H:i'),
                'end_time' => $booking->ends_at->format('H:i'),
            ];
        } else {
            Gate::authorize('create', Booking::class);

            $start = $startTime ?? '09:00';
            $this->form = [
                'room_id' => $roomId ?? '',
                'date' => $date ?? now()->toDateString(),
                'start_time' => $start,
                'end_time' => CarbonImmutable::createFromFormat('H:i', $start)->addMinutes(30)->format('H:i'),
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
            Room::query()->whereKey($validated['form']['room_id'])->firstOrFail(),
            $startsAt,
            $endsAt,
            $booking,
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
            'form.room_id' => [
                'required',
                'integer',
                Rule::exists((new Room)->getTable(), 'id')->where('is_active', true),
            ],
            'form.date' => ['required', 'date_format:Y-m-d'],
            'form.start_time' => ['required', 'date_format:H:i'],
            'form.end_time' => ['required', 'date_format:H:i'],
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
        return view('heya::livewire.booking-form', [
            'rooms' => Room::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }
}
