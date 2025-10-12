<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationsController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications/{user_id}', [NotificationsController::class, 'index']);
    Route::post('/notifications', [NotificationsController::class, 'store']);
    Route::post('/notifications/{id}/read', [NotificationsController::class, 'markAsRead']);
});