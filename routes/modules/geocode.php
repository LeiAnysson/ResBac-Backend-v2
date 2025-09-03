<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeocodeController;

Route::middleware(['auth:sanctum', 'role:MDRRMO'])->group(function () {
    Route::post('/geocode', [GeocodeController::class, 'geocode']);
});