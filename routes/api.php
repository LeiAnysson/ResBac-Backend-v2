<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OCRController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminResidentController;
use App\Http\Controllers\IncidentReportController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::post('/ocr-upload', [OCRController::class, 'upload']);

//--------------------

Route::get('/admin/users', [UserController::class, 'index']);
Route::post('/admin/users/{id}/assign-role', [UserController::class, 'assignRole']);
Route::post('/admin/users/create', [UserController::class, 'createAccount']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin/residents', [AdminResidentController::class, 'index']);
    Route::post('/admin/residents/{id}/approve', [AdminResidentController::class, 'approve']);
    Route::post('/admin/residents/{id}/reject', [AdminResidentController::class, 'reject']);
    Route::get('/admin/residents/{id}', [AdminResidentController::class, 'show']);
});

//---------------------

Route::get('/admin/incidents', [IncidentReportController::class, 'index']);
Route::get('/admin/incidents/{id}', [IncidentReportController::class, 'show']);
