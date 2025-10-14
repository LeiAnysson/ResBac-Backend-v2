<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HeatmapController;

Route::get('/heatmap/incidents', [HeatmapController::class, 'incidentsByBarangay']);
