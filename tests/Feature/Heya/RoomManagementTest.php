<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Heya\Livewire\RoomManagement;
use Modules\Heya\Models\Booking;
use Modules\Heya\Models\Room;

it('allows an authenticated user to create and edit a room', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(RoomManagement::class)
        ->call('create')
        ->set('form.name', '大会議室')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('大会議室');

    $room = Room::query()->where('name', '大会議室')->sole();

    $component->call('edit', $room->id)
        ->set('form.name', '大会議室 2')
        ->call('save')
        ->assertHasNoErrors();

    expect($room->fresh()->name)->toBe('大会議室 2');
});

it('requires unique room names', function (): void {
    $user = User::factory()->create();
    Room::factory()->create(['name' => '会議室 A']);

    Livewire::actingAs($user)
        ->test(RoomManagement::class)
        ->call('create')
        ->set('form.name', '会議室 A')
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique']);
});

it('deactivates a room without deleting its bookings', function (): void {
    $user = User::factory()->create();
    $room = Room::factory()->create();
    $booking = Booking::factory()->for($room)->create();

    Livewire::actingAs($user)
        ->test(RoomManagement::class)
        ->call('toggleActive', $room->id);

    expect($room->fresh()->is_active)->toBeFalse()
        ->and($booking->fresh())->not->toBeNull();
});
