<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResponseTeamController;
use Illuminate\Support\Facades\Cache;

Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('admin/teams')->group(function () {
    Route::post('/create-team', [ResponseTeamController::class, 'store']);
    Route::get('/{id}', [ResponseTeamController::class, 'show']); 
    Route::put('/{id}', [ResponseTeamController::class, 'update']); 
    Route::post('/{id}/add-member', [ResponseTeamController::class, 'addMember']);
    Route::delete('/{teamId}/remove-member/{memberId}', [ResponseTeamController::class, 'removeMember']);
    Route::put('/rotation/start-date', [ResponseTeamController::class, 'setRotationStartDate']);
    Route::get('rotation/start-date', function () {
        return response()->json(['rotation_start_date' => Cache::get('rotation_start_date')]);
    });
    Route::delete('/{id}', [ResponseTeamController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'role:Admin,MDRRMO'])->prefix('admin/teams')->group(function () {
    Route::get('/', [ResponseTeamController::class, 'index']);
});