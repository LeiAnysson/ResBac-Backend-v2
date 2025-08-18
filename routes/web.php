<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BackupController;

Route::get('/', function () {
    return view('welcome');
});

// Backup
Route::get('/backup-database', [BackupController::class, 'backup'])->middleware('auth', 'role:admin');

// Restore
Route::post('/restore-database', [BackupController::class, 'restore'])->middleware('auth', 'role:admin');
