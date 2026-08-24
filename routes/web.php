<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('dashboard', 'souko/rental-counter')->name('dashboard');
});

require __DIR__.'/settings.php';
