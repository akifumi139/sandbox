<?php

use Illuminate\Support\Facades\Route;
use Modules\Calendar\Livewire\TaskManager;

Route::middleware(['auth', 'verified'])
    ->get('calendar', TaskManager::class)
    ->name('calendar.index');
