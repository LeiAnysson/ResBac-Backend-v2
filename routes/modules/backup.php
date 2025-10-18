<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackupController;

Route::get('/backup/download', [BackupController::class, 'backup']);
Route::get('/backup/scheduled', [BackupController::class, 'scheduledBackup']);
Route::post('/restore', [BackupController::class, 'restore']);
