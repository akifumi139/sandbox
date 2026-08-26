<?php

namespace Modules\Syako\Actions;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Syako\Models\Booking;
use Modules\Syako\Models\Vehicle;

class SaveBooking
{
    public function handle(
        User $user,
        Vehicle $vehicle,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?Booking $booking = null,
        ?string $notes = null,
        bool $hasFuelCard = false,
        bool $hasEtcCard = false,
    ): Booking {
        return DB::transaction(function () use ($user, $vehicle, $startsAt, $endsAt, $booking, $notes, $hasFuelCard, $hasEtcCard): Booking {
            $lockedVehicle = Vehicle::query()->whereKey($vehicle->getKey())->lockForUpdate()->firstOrFail();

            if (! $lockedVehicle->is_active) {
                throw ValidationException::withMessages([
                    'form.vehicle_id' => 'この車両は現在予約できません。',
                ]);
            }

            $hasConflict = Booking::query()
                ->whereBelongsTo($lockedVehicle)
                ->when($booking !== null, fn ($query) => $query->whereKeyNot($booking->getKey()))
                ->where('starts_at', '<', $endsAt)
                ->where('ends_at', '>', $startsAt)
                ->exists();

            if ($hasConflict) {
                throw ValidationException::withMessages([
                    'form.starts_at' => '選択した時間にはすでに予約があります。',
                ]);
            }

            $savedBooking = $booking ?? new Booking;

            if (! $savedBooking->exists) {
                $savedBooking->user()->associate($user);
            }

            $savedBooking->vehicle()->associate($lockedVehicle);
            $savedBooking->fill([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'notes' => $notes,
                'has_fuel_card' => $hasFuelCard,
                'has_etc_card' => $hasEtcCard,
            ])->save();

            return $savedBooking->refresh();
        });
    }
}
