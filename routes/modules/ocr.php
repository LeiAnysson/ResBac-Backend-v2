<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OCRController;

Route::post('/ocr-upload', [OCRController::class, 'upload']);
