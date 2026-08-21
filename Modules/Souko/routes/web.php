<?php

use Illuminate\Support\Facades\Route;
use Modules\Souko\Livewire\BorrowHistory;
use Modules\Souko\Livewire\Inventory;
use Modules\Souko\Livewire\RentalCounter;
use Modules\Souko\Livewire\ReturnCounter;

Route::middleware(['auth', 'verified'])
    ->prefix('souko')
    ->name('souko.')
    ->group(function () {
        Route::get('rental-counter', RentalCounter::class)->name('rental-counter');
        Route::get('return-counter', ReturnCounter::class)->name('return-counter');

        Route::get('inventory', Inventory::class)->name('inventory');
        Route::get('borrow-history', BorrowHistory::class)->name('borrow-history');
    });
