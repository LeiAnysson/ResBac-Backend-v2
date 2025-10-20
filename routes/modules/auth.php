<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\RequestDecryptionMiddleware;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login'])->middleware(RequestDecryptionMiddleware::class);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
});

Route::get('/reset-password/{id}', [AuthController::class, 'showResetForm'])
    ->name('password.reset')
    ->middleware('signed'); 

Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
