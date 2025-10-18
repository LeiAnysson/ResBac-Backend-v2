<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResponderControllers\ResponderProfileController;

Route::middleware(['auth:sanctum', 'role:Responder'])->group(function () {
    Route::get('/responders/{id}', [ResponderProfileController::class, 'show']);
    Route::put('/responders/{id}', [ResponderProfileController::class, 'update']);
});
