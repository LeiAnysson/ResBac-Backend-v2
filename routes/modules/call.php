<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgoraController;

Route::get('/agora/token', [AgoraController::class, 'generateToken']);