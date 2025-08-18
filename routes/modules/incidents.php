<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidentReportController;

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::get('/admin/incidents', [IncidentReportController::class, 'index']);
    Route::get('/admin/incidents/{id}', [IncidentReportController::class, 'show']);
    Route::get('/admin/incidents/weekly-reports', [IncidentReportController::class, 'reportsResolvedThisWeek']);
});

