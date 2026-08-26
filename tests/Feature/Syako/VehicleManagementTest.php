<?php

use App\Models\User;
use Livewire\Livewire;
use Modules\Syako\Livewire\VehicleManagement;
use Modules\Syako\Models\Booking;
use Modules\Syako\Models\Vehicle;

it('allows an authenticated user to create and edit a vehicle', function (): void {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('create')
        ->set('form.name', '車両 A')
        ->set('form.vehicle_number', '品川 500 あ 1001')
        ->set('form.manufacturer', 'トヨタ')
        ->set('form.model', 'プリウス')
        ->set('form.model_code', 'ZVW50')
        ->call('save')
        ->assertHasNoErrors();

    $vehicle = Vehicle::query()->where('name', '車両 A')->sole();

    expect($vehicle->vehicle_number)->toBe('品川 500 あ 1001')
        ->and($vehicle->manufacturer)->toBe('トヨタ')
        ->and($vehicle->model)->toBe('プリウス')
        ->and($vehicle->model_code)->toBe('ZVW50');

    $component->call('edit', $vehicle->id)
        ->set('form.vehicle_number', '品川 500 あ 1002')
        ->call('save')
        ->assertHasNoErrors();

    expect($vehicle->fresh()->vehicle_number)->toBe('品川 500 あ 1002');
});

it('requires unique vehicle numbers', function (): void {
    $user = User::factory()->create();
    Vehicle::factory()->create(['vehicle_number' => '品川 500 あ 1001']);

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('create')
        ->set('form.name', '車両 B')
        ->set('form.vehicle_number', '品川 500 あ 1001')
        ->call('save')
        ->assertHasErrors(['form.vehicle_number' => 'unique']);
});

it('soft deletes a vehicle without bookings', function (): void {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('delete', $vehicle->id);

    expect(Vehicle::query()->whereKey($vehicle->id)->exists())->toBeFalse()
        ->and(Vehicle::withTrashed()->whereKey($vehicle->id)->exists())->toBeTrue();
});

it('does not delete a vehicle with bookings', function (): void {
    $user = User::factory()->create();
    $vehicle = Vehicle::factory()->create();
    Booking::factory()->for($vehicle)->for($user)->create();

    Livewire::actingAs($user)
        ->test(VehicleManagement::class)
        ->call('delete', $vehicle->id);

    expect(Vehicle::query()->whereKey($vehicle->id)->exists())->toBeTrue();
});
