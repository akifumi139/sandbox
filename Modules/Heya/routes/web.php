<?php

use Illuminate\Support\Facades\Route;
use Modules\Heya\Livewire\RoomBookingDaily;
use Modules\Heya\Livewire\RoomBookingMonthly;
use Modules\Heya\Livewire\RoomManagement;

Route::middleware(['auth', 'verified'])
    ->prefix('heya')
    ->name('heya.')
    ->group(function () {
        Route::get('daily', RoomBookingDaily::class)->name('daily');
        Route::get('monthly', RoomBookingMonthly::class)->name('monthly');
        Route::get('rooms', RoomManagement::class)->name('rooms');
    });
