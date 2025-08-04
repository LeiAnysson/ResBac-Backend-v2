<?php

use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OCRController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminResidentController;
use App\Http\Controllers\IncidentReportController;
use App\Http\Controllers\ResponseTeamController;
use Illuminate\Http\Request;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::post('/ocr-upload', [OCRController::class, 'upload']);

//-------------------- navbar profile

Route::middleware('auth:sanctum')->get('/me', [AuthController::class, 'me']);

//--------------------

Route::get('/admin/users', [UserController::class, 'index']);
Route::post('/admin/users/{id}/assign-role', [UserController::class, 'assignRole']);
Route::post('/admin/users/create', [UserController::class, 'createAccount']);
Route::get('/admin/users/total-users', [UserController::class, 'totalUsers']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/admin/residents', [AdminResidentController::class, 'index']);
    Route::post('/admin/residents/{id}/approve', [AdminResidentController::class, 'approve']);
    Route::post('/admin/residents/{id}/reject', [AdminResidentController::class, 'reject']);
    Route::get('/admin/residents/{id}', [AdminResidentController::class, 'show']);
    Route::get('/admin/residents/pending-residents', [AdminResidentController::class, 'pendingResidentsCount']);
});



//---------------------

Route::get('/admin/incidents', [IncidentReportController::class, 'index']);
Route::get('/admin/incidents/{id}', [IncidentReportController::class, 'show']);
Route::get('/admin/incidents/weekly-reports', [IncidentReportController::class, 'reportsResolvedThisWeek']);

//---------------------

Route::get('/admin/teams', [ResponseTeamController::class, 'index']);

//---------------------

Route::get('/admin/activity-logs', [ActivityLogController::class, 'index']);