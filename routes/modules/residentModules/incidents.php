<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResidentControllers\ResidentReportController;

Route::middleware(['auth:sanctum', 'role:Resident'])->group(function () {
    Route::get('/resident/reports', [ResidentReportController::class, 'index']);
});
