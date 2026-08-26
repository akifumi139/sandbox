<?php

use App\Models\User;
use Modules\Syako\Models\Booking;
use Modules\Syako\Models\Vehicle;

it('persists vehicles and connects them to bookings', function (): void {
    $vehicle = Vehicle::factory()->create();
    $user = User::factory()->create();
    $booking = Booking::factory()->for($vehicle)->for($user)->create();

    expect($vehicle->getTable())->toBe('syako__vehicles')
        ->and($booking->vehicle_id)->toBe($vehicle->id)
        ->and($booking->vehicle->is($vehicle))->toBeTrue()
        ->and($vehicle->bookings->first()->is($booking))->toBeTrue();
});

it('soft deletes vehicles', function (): void {
    $vehicle = Vehicle::factory()->create();

    $vehicle->delete();

    expect(Vehicle::query()->whereKey($vehicle->id)->exists())->toBeFalse()
        ->and(Vehicle::withTrashed()->whereKey($vehicle->id)->exists())->toBeTrue()
        ->and($vehicle->fresh()->deleted_at)->not->toBeNull();
});
