<?php

namespace Modules\Syako\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Modules\Syako\Models\Booking;

class BookingPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }
}
