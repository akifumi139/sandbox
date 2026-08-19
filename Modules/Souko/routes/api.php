<?php

use Illuminate\Support\Facades\Route;
use Modules\Souko\Http\Controllers\SoukoController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('soukos', SoukoController::class)->names('souko');
});
