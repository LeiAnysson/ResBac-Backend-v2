<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnnouncementController;

Route::middleware(['auth:sanctum', 'role:Admin,MDRRMO'])->group(function () {
    Route::get('/announcements', [AnnouncementController::class, 'index']);
});