<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Heya\Livewire\BookingForm;
use Modules\Heya\Models\Booking;
use Modules\Heya\Models\Room;

it('opens with slot defaults and creates a booking', function (): void {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    Livewire::actingAs($user)
        ->test(BookingForm::class)
        ->dispatch('open-booking-form', roomId: $room->id, date: '2026-08-25', startTime: '10:00')
        ->assertSet('showModal', true)
        ->assertSet('form.end_time', '10:30')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false)
        ->assertDispatched('booking-saved');

    $booking = Booking::query()->sole();

    expect($booking->room->is($room))->toBeTrue()
        ->and($booking->user->is($user))->toBeTrue()
        ->and($booking->starts_at->format('Y-m-d H:i'))->toBe('2026-08-25 10:00');
});

it('validates working hours and thirty minute boundaries', function (): void {
    $user = User::factory()->create();
    $room = Room::factory()->create();

    Livewire::actingAs($user)
        ->test(BookingForm::class)
        ->dispatch('open-booking-form', roomId: $room->id, date: '2026-08-25')
        ->set('form.start_time', '06:45')
        ->set('form.end_time', '07:15')
        ->call('save')
        ->assertHasErrors(['form.start_time']);

    expect(Booking::query()->exists())->toBeFalse();
});

it('opens another users booking as read only', function (): void {
    $booking = Booking::factory()->create();
    $otherUser = User::factory()->create();

    Livewire::actingAs($otherUser)
        ->test(BookingForm::class)
        ->dispatch('open-booking-form', bookingId: $booking->id)
        ->assertSet('showModal', true)
        ->assertSet('readOnly', true)
        ->assertSet('form.room_id', $booking->room_id);
});

it('allows the owner to delete a booking', function (): void {
    $owner = User::factory()->create();
    $booking = Booking::factory()->for($owner)->create();

    Livewire::actingAs($owner)
        ->test(BookingForm::class)
        ->dispatch('open-booking-form', bookingId: $booking->id)
        ->assertSet('readOnly', false)
        ->call('delete')
        ->assertDispatched('booking-deleted');

    expect($booking->fresh())->toBeNull();
});
