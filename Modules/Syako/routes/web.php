<?php

use Illuminate\Support\Facades\Route;
use Modules\Syako\Livewire\VehicleBookingMonthly;
use Modules\Syako\Livewire\VehicleManagement;

Route::middleware(['auth', 'verified'])
    ->prefix('syako')
    ->name('syako.')
    ->group(function () {
        Route::get('monthly', VehicleBookingMonthly::class)->name('monthly');
        Route::get('vehicle-management', VehicleManagement::class)->name('vehicle-management');
    });
