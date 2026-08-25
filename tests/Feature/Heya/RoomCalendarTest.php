<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Heya\Livewire\RoomBookingDaily;
use Modules\Heya\Livewire\RoomBookingMonthly;
use Modules\Heya\Models\Booking;
use Modules\Heya\Models\Room;

it('shows and navigates daily bookings', function (): void {
    $user = User::factory()->create();
    $room = Room::factory()->create(['name' => '会議室 A']);
    Booking::factory()->for($room)->for($user)->create([
        'starts_at' => '2026-08-25 09:00:00',
        'ends_at' => '2026-08-25 10:00:00',
    ]);

    Livewire::actingAs($user)
        ->withQueryParams(['date' => '2026-08-25'])
        ->test(RoomBookingDaily::class)
        ->assertSee($user->name)
        ->call('nextDay')
        ->assertSet('selectedDate', '2026-08-26')
        ->assertDontSee($user->name);

    $calendar = app(RoomBookingDaily::class);
    $calendar->selectedDate = '2026-08-26';
    $calendar->updatedSelectedDate();

    expect($calendar->bookings())->toBeEmpty();
});

it('filters daily bookings by room', function (): void {
    $visibleRoom = Room::factory()->create(['name' => '会議室 A']);
    $hiddenRoom = Room::factory()->create(['name' => '会議室 B']);
    $visibleUser = User::factory()->create(['name' => '表示対象ユーザー']);
    $hiddenUser = User::factory()->create(['name' => '非表示対象ユーザー']);
    Booking::factory()->for($visibleRoom)->for($visibleUser)->create(['starts_at' => '2026-08-25 09:00', 'ends_at' => '2026-08-25 09:30']);
    Booking::factory()->for($hiddenRoom)->for($hiddenUser)->create(['starts_at' => '2026-08-25 09:00', 'ends_at' => '2026-08-25 09:30']);

    $component = app(RoomBookingDaily::class);
    $component->selectedDate = '2026-08-25';
    $component->selectedRoom = (string) $visibleRoom->id;

    expect($component->rooms())->toHaveCount(1)
        ->and($component->rooms()->first()->is($visibleRoom))->toBeTrue()
        ->and($component->bookings())->toHaveCount(1)
        ->and($component->bookings()->first()->user->is($visibleUser))->toBeTrue();
});

it('builds a six week monthly calendar and groups bookings by date', function (): void {
    $user = User::factory()->create();
    $booking = Booking::factory()->create([
        'starts_at' => '2026-08-25 13:00',
        'ends_at' => '2026-08-25 13:30',
    ]);

    $component = Livewire::actingAs($user)
        ->withQueryParams(['month' => '2026-08'])
        ->test(RoomBookingMonthly::class)
        ->assertSeeHtml('type="month"')
        ->assertSeeHtml('wire:model.change="currentMonth"')
        ->assertSee($booking->room->name);

    expect($component->get('calendarDays'))->toHaveCount(42)
        ->and($component->get('bookingsByDate')->get('2026-08-25')->first()->is($booking))->toBeTrue();

    $calendar = app(RoomBookingMonthly::class);
    $calendar->currentMonth = '2026-09';
    $calendar->updatedCurrentMonth();

    expect($calendar->calendarDays())->toHaveCount(42)
        ->and($calendar->calendarDays()->first()->toDateString())->toBe('2026-08-30');
});
