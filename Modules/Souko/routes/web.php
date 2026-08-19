<?php

use Illuminate\Support\Facades\Route;
use Modules\Souko\Livewire\BorrowHistory;
use Modules\Souko\Livewire\Inventory;
use Modules\Souko\Livewire\QrScanner;

Route::middleware(['auth', 'verified'])
    ->prefix('souko')
    ->name('souko.')
    ->group(function () {
        Route::get('qr-scanner', QrScanner::class)->name('qr-scanner');
        Route::get('inventory', Inventory::class)->name('inventory');
        Route::get('borrow-history', BorrowHistory::class)->name('borrow-history');
    });
