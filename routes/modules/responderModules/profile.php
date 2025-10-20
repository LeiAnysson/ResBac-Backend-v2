<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResponderControllers\ResponderProfileController;
use App\Http\Controllers\ResidentControllers\ResidentProfileController;

Route::middleware(['auth:sanctum', 'role:Responder'])->group(function () {
    Route::get('/responders/{id}', [ResponderProfileController::class, 'show']);
    Route::put('/responders/{id}', [ResponderProfileController::class, 'update']);
    Route::post('/responders/profile-image', [ResidentProfileController::class, 'updateProfileImage']);
});
