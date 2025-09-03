<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResponseTeamController;

Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('admin/teams')->group(function () {
    Route::get('/{id}', [ResponseTeamController::class, 'show']); 
    Route::put('/{id}', [ResponseTeamController::class, 'update']); 
    Route::post('/{id}/add-member', [ResponseTeamController::class, 'addMember']);
    Route::delete('/{teamId}/remove-member/{memberId}', [ResponseTeamController::class, 'removeMember']);
    Route::put('/rotation/start-date', [ResponseTeamController::class, 'setRotationStartDate']);
    Route::post('/rotate', [ResponseTeamController::class, 'rotateTeams']);
});

Route::middleware(['auth:sanctum', 'role:Admin,MDRRMO'])->prefix('admin/teams')->group(function () {
    Route::get('/', [ResponseTeamController::class, 'index']);
});