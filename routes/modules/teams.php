<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResponseTeamController;

Route::middleware(['auth:sanctum', 'role:Admin'])->prefix('admin/teams')->group(function () {
    Route::get('/', [ResponseTeamController::class, 'index']);
    Route::get('/{id}', [ResponseTeamController::class, 'show']); 
    Route::put('/{id}', [ResponseTeamController::class, 'update']); 
    Route::post('/{id}/add-member', [ResponseTeamController::class, 'addMember']);
    Route::delete('/{teamId}/remove-member/{memberId}', [ResponseTeamController::class, 'removeMember']);
});