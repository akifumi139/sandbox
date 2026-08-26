<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Syako\Livewire\BookingForm;
use Modules\Syako\Models\Booking;
use Modules\Syako\Models\Vehicle;

it('creates a booking with notes and requested cards', function (): void {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    Livewire::actingAs($user)
        ->test(BookingForm::class)
        ->dispatch('open-booking-form', vehicleId: $vehicle->id, date: '2026-08-25', startTime: '10:00')
        ->set('form.notes', '満タン返却')
        ->set('form.has_fuel_card', true)
        ->set('form.has_etc_card', true)
        ->call('save')
        ->assertHasNoErrors();

    $booking = Booking::query()->sole();

    expect($booking->notes)->toBe('満タン返却')
        ->and($booking->has_fuel_card)->toBeTrue()
        ->and($booking->has_etc_card)->toBeTrue();
});

it('loads booking details when editing', function (): void {
    $user = User::factory()->create();
    $booking = Booking::factory()->for($user)->create([
        'notes' => '車庫に戻す',
        'has_fuel_card' => true,
        'has_etc_card' => false,
    ]);

    Livewire::actingAs($user)
        ->test(BookingForm::class)
        ->dispatch('open-booking-form', bookingId: $booking->id)
        ->assertSet('form.notes', '車庫に戻す')
        ->assertSet('form.has_fuel_card', true)
        ->assertSet('form.has_etc_card', false);
});

it('creates an all-day booking spanning multiple days', function (): void {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    Livewire::actingAs($user)
        ->test(BookingForm::class)
        ->dispatch('open-booking-form', vehicleId: $vehicle->id, date: '2026-08-25')
        ->set('form.end_date', '2026-08-27')
        ->set('form.all_day', true)
        ->call('save')
        ->assertHasNoErrors();

    $booking = Booking::query()->sole();

    expect($booking->starts_at->format('Y-m-d H:i'))->toBe('2026-08-25 00:00')
        ->and($booking->ends_at->format('Y-m-d H:i'))->toBe('2026-08-28 00:00')
        ->and($booking->isAllDay())->toBeTrue();
});
