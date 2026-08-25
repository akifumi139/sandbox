<?php

use App\Models\User;
use Carbon\CarbonInterface;
use Modules\Heya\Models\Booking;
use Modules\Heya\Models\Room;

it('persists rooms with their active state', function (): void {
    $room = Room::factory()->create([
        'is_active' => true,
    ]);

    expect($room->is_active)->toBeTrue();
});

it('connects bookings to their room and owner', function (): void {
    $room = Room::factory()->create();
    $user = User::factory()->create();
    $booking = Booking::factory()->for($room)->for($user)->create();

    expect($booking->starts_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($booking->ends_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($booking->room->is($room))->toBeTrue()
        ->and($booking->user->is($user))->toBeTrue()
        ->and($room->bookings->first()->is($booking))->toBeTrue()
        ->and($user->bookings->first()->is($booking))->toBeTrue();
});
