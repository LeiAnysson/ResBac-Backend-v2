<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResidentControllers\ResidentAnnouncementController;

Route::middleware(['auth:sanctum', 'role:Resident'])->group(function () {
    Route::get('/resident/announcements', [ResidentAnnouncementController::class, 'index']);
});