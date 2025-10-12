<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ResponderControllers\ResponderReportController;
use Ably\Models\TokenParams;
use Ably\AblyRest;

Route::middleware(['auth:sanctum', 'role:Responder'])->group(function () {
    Route::get('/responder/reports', [ResponderReportController::class, 'index']);
    Route::post('/responder/update-location', [ResponderReportController::class, 'updateLocation']);
    Route::get('/responder/{teamId}/location', [ResponderReportController::class, 'getLocation']);
    Route::get('/responder/report/{id}', [ResponderReportController::class, 'show']);
    Route::post('/responder/report/{id}/update-status', [ResponderReportController::class, 'updateStatus']);
    Route::post('/responder/report/{id}/request-backup', [ResponderReportController::class, 'requestBackup']);

    Route::get('/responder/ably-token', function () {
        $ably = new AblyRest(env('ABLY_KEY'));

        $params = new TokenParams([
            'capability' => json_encode([
                'responder-location' => ['publish', 'subscribe'],
            ]),
            'ttl' => 60000,
        ]);

        $tokenRequest = $ably->auth->createTokenRequest($params);

        return response()->json($tokenRequest);
    });
});
