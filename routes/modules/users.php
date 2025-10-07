<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminResidentController;

Route::middleware(['auth:sanctum', 'role:Admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'index']);
    Route::get('/admin/users/{id}', [AdminResidentController::class, 'show']);
    Route::post('/admin/users/{id}/assign-role', [UserController::class, 'assignRole']);
    Route::post('/admin/users/create', [UserController::class, 'createAccount']);
    Route::put('/admin/users/{id}/edit', [UserController::class, 'editUser']);
    Route::get('/admin/users/total-users', [UserController::class, 'totalUsers']);
});

