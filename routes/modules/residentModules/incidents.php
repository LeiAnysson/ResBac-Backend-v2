<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResidentControllers\ResidentReportController;
use App\Http\Controllers\ResponderControllers\ResponderReportController;

Route::middleware(['auth:sanctum', 'role:Resident'])->group(function () {
    Route::get('/resident/reports', [ResidentReportController::class, 'index']);
    Route::get('/responder/{teamId}/location', [ResponderReportController::class, 'getLocation']);
    Route::get('/resident/reports/with-team', [ResidentReportController::class, 'indexWithTeam']);
    Route::patch('/resident/reports/{id}/add-details', [ResidentReportController::class, 'addDetailsToUnanswered']);
});
