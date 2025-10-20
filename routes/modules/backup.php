<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackupController;

Route::get('/backup/download', [BackupController::class, 'backup']);
Route::get('/backup/scheduled', [BackupController::class, 'scheduledBackup']);
Route::post('/backup/schedule', [BackupController::class, 'saveSchedule']);

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::post('/restore', [BackupController::class, 'restore']);
});



