<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\IncidentTypeController;

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::get('/admin/incident-types', [IncidentTypeController::class, 'index']);
    Route::put('/admin/incident-types/{id}', [IncidentTypeController::class, 'update']);
});

Route::get('/incident-types', [IncidentTypeController::class, 'index']);

