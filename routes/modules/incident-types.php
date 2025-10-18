<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidentTypeController;

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::get('/admin/incident-types', [IncidentTypeController::class, 'index']);
    Route::get('/admin/incident-types/all', [IncidentTypeController::class, 'allIncidentTypes']);
    Route::get('/admin/priorities', [IncidentTypeController::class, 'priorities']);
    Route::post('/admin/incident-types', [IncidentTypeController::class, 'store']);
    Route::put('/admin/incident-types/{id}', [IncidentTypeController::class, 'update']);
    Route::delete('/admin/incident-types/{id}', [IncidentTypeController::class, 'destroy']);
});

Route::get('/incident-types', [IncidentTypeController::class, 'index']);

