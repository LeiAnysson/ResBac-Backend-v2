<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnnouncementController;

Route::middleware(['auth:sanctum', 'role:Admin,MDRRMO'])->group(function () {
    Route::get('/admin/announcements', [AnnouncementController::class, 'index']);
    Route::post('/admin/announcements/create', [AnnouncementController::class, 'store']);
    Route::delete('/admin/announcements/{id}', [AnnouncementController::class, 'destroy']);
    Route::post('/admin/announcements/upload-image', [AnnouncementController::class, 'uploadAnnouncementImage']);
});