<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::post('/admin/users/{id}/assign-role', [UserController::class, 'assignRole']);
    Route::post('/admin/users/create', [UserController::class, 'createAccount']);
    Route::get('/admin/users/total-users', [UserController::class, 'totalUsers']);
});

