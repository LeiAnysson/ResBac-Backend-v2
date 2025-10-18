<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResidentControllers\ResidentProfileController;

Route::middleware(['auth:sanctum', 'role:Resident'])->group(function () {
    Route::get('/residents/{id}', [ResidentProfileController::class, 'show']);
    Route::put('/residents/{id}', [ResidentProfileController::class, 'update']);
});
