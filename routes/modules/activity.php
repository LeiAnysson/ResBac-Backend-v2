<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActivityLogController;

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::get('/admin/activity-logs', [ActivityLogController::class, 'index']);
    Route::get('/admin/activity-logs/all', [ActivityLogController::class, 'all']);
});