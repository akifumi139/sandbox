<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Heya\Actions\SaveBooking;
use Modules\Heya\Models\Booking;
use Modules\Heya\Models\Room;

it('creates a booking for an active room', function (): void {
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $startsAt = CarbonImmutable::parse('2026-08-25 09:00');

    $booking = app(SaveBooking::class)->handle(
        $user,
        $room,
        $startsAt,
        $startsAt->addHour(),
    );

    expect($booking->user->is($user))->toBeTrue()
        ->and($booking->room->is($room))->toBeTrue();
});

it('rejects overlapping bookings but permits adjacent bookings', function (): void {
    $room = Room::factory()->create();
    $existing = Booking::factory()->for($room)->create([
        'starts_at' => '2026-08-25 09:00:00',
        'ends_at' => '2026-08-25 10:00:00',
    ]);
    $action = app(SaveBooking::class);

    $adjacent = $action->handle(
        User::factory()->create(),
        $room,
        CarbonImmutable::parse('2026-08-25 10:00'),
        CarbonImmutable::parse('2026-08-25 10:30'),
    );

    expect($adjacent)->toBeInstanceOf(Booking::class);

    expect(fn () => $action->handle(
        User::factory()->create(),
        $room,
        CarbonImmutable::parse('2026-08-25 09:30'),
        CarbonImmutable::parse('2026-08-25 10:30'),
    ))->toThrow(ValidationException::class);

    expect($existing->fresh())->not->toBeNull();
});

it('excludes the booking being updated from conflict detection', function (): void {
    $user = User::factory()->create();
    $booking = Booking::factory()->for($user)->create([
        'starts_at' => '2026-08-25 09:00:00',
        'ends_at' => '2026-08-25 10:00:00',
    ]);

    $updated = app(SaveBooking::class)->handle(
        $user,
        $booking->room,
        CarbonImmutable::parse('2026-08-25 09:00'),
        CarbonImmutable::parse('2026-08-25 10:30'),
        $booking,
    );

    expect($updated->user->is($user))->toBeTrue()
        ->and($updated->ends_at->format('H:i'))->toBe('10:30');
});

it('rejects bookings for inactive rooms', function (): void {
    $room = Room::factory()->create(['is_active' => false]);

    expect(fn () => app(SaveBooking::class)->handle(
        User::factory()->create(),
        $room,
        CarbonImmutable::parse('2026-08-25 09:00'),
        CarbonImmutable::parse('2026-08-25 09:30'),
    ))->toThrow(ValidationException::class);
});

it('only allows the owner to update or delete a booking', function (): void {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $booking = Booking::factory()->for($owner)->create();

    expect(Gate::forUser($owner)->allows('update', $booking))->toBeTrue()
        ->and(Gate::forUser($owner)->allows('delete', $booking))->toBeTrue()
        ->and(Gate::forUser($otherUser)->allows('view', $booking))->toBeTrue()
        ->and(Gate::forUser($otherUser)->denies('update', $booking))->toBeTrue()
        ->and(Gate::forUser($otherUser)->denies('delete', $booking))->toBeTrue();
});
