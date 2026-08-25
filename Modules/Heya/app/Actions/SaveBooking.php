<?php

namespace Modules\Heya\Actions;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Heya\Models\Booking;
use Modules\Heya\Models\Room;

class SaveBooking
{
    public function handle(
        User $user,
        Room $room,
        CarbonInterface $startsAt,
        CarbonInterface $endsAt,
        ?Booking $booking = null,
    ): Booking {
        return DB::transaction(function () use ($user, $room, $startsAt, $endsAt, $booking): Booking {
            $lockedRoom = Room::query()->whereKey($room->getKey())->lockForUpdate()->firstOrFail();

            if (! $lockedRoom->is_active) {
                throw ValidationException::withMessages([
                    'form.room_id' => 'この会議室は現在予約できません。',
                ]);
            }

            $hasConflict = Booking::query()
                ->whereBelongsTo($lockedRoom)
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

            $savedBooking->room()->associate($lockedRoom);
            $savedBooking->fill([
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ])->save();

            return $savedBooking->refresh();
        });
    }
}
