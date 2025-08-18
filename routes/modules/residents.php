<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminResidentController;

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::get('/admin/residents', [AdminResidentController::class, 'index']);
    Route::post('/admin/residents/{id}/approve', [AdminResidentController::class, 'approve']);
    Route::post('/admin/residents/{id}/reject', [AdminResidentController::class, 'reject']);
    Route::get('/admin/residents/{id}', [AdminResidentController::class, 'show']);
    Route::get('/admin/residents/pending-residents', [AdminResidentController::class, 'pendingResidentsCount']);
});
