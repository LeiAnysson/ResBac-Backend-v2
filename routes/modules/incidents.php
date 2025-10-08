<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidentReportController;
use App\Http\Controllers\IncidentCallerController;
use App\Http\Controllers\IncidentUpdateController;
use App\Http\Controllers\ResponseTeamAssignmentController;

Route::middleware(['auth:sanctum', 'role:Admin,MDRRMO'])->group(function () {
    Route::get('/incidents', [IncidentReportController::class, 'index']);
    Route::get('/incidents/{id}', [IncidentReportController::class, 'show']);
    Route::delete('/incidents/{id}', [IncidentReportController::class, 'destroy']);
    Route::get('/incidents/weekly-reports', [IncidentReportController::class, 'reportsResolvedThisWeek']);
});

Route::middleware(['auth:sanctum', 'role:MDRRMO'])->group(function () {
    Route::post('incidents/{incident_id}/callers', [IncidentCallerController::class, 'store']);
    Route::post('incidents/{incident_id}/updates', [IncidentUpdateController::class, 'store']);
    Route::post('incidents/{incident}/mark-invalid', [IncidentReportController::class, 'markInvalid']);

    Route::post('incidents/calls/accept/{incident_id}', [IncidentReportController::class, 'acceptCall']);

    Route::get('incidents/active-calls', [IncidentReportController::class, 'fetchActiveCalls']);
    Route::post('incidents/{id}/assign-team', [ResponseTeamAssignmentController::class, 'store']);
    Route::put('incidents/team-assignments/{id}', [ResponseTeamAssignmentController::class, 'update']);
    Route::get('incidents/active', [IncidentReportController::class, 'getActiveIncidents']);
});

Route::middleware(['auth:sanctum', 'role:Resident'])->group(function () {
    Route::post('incidents/from-resident', [IncidentReportController::class, 'storeFromResident']);
});

Route::middleware(['auth:sanctum', 'role:Resident,MDRRMO'])->group(function () {
    Route::post('incidents/calls/{incidentId}/end', [IncidentReportController::class, 'endCall']);
});
