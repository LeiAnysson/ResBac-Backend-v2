<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResponderControllers\ResponderReportController;

Route::middleware(['auth:sanctum', 'role:Responder'])->group(function () {
    Route::get('/responder/reports', [ResponderReportController::class, 'index']);
    Route::post('/responder/update-location', [ResponderReportController::class, 'updateLocation']);
    Route::get('/responder/{teamId}/location', [ResponderReportController::class, 'getLocation']);
    Route::get('/responder/report/{id}', [ResponderReportController::class, 'show']);
    Route::post('/responder/report/{id}/update-status', [ResponderReportController::class, 'updateStatus']);
    Route::post('/responder/report/{id}/request-backup', [ResponderReportController::class, 'requestBackup']);
});
