<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Ably\AblyRest;
use App\Events\NotificationEvent;
use Ably\Models\TokenParams;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

foreach (glob(__DIR__ . '/modules/*.php') as $routeFile) {
    require $routeFile;
}
foreach (glob(__DIR__ . '/modules/residentModules/*.php') as $routeFile) {
    require $routeFile;
}

/*
|--------------------------------------------------------------------------
| Broadcasting Auth Route for API
|--------------------------------------------------------------------------
|
| This allows Laravel Echo (from frontend) to authenticate private channels
| using JWT tokens sent in Authorization header.
|
*/

Route::post('/notify', function (Request $request) {
    $request->validate([
        'channel' => 'required|string',
        'title' => 'required|string',
        'body' => 'required|string',
    ]);

    broadcast(new NotificationEvent(
        $request->channel,
        $request->message
    ))->toOthers();

    return response()->json(['status' => 'Notification sent']);
});

// Route::post('/ably-auth', function () {
//     ob_clean();
//     $apiKey = env('ABLY_KEY');
//     if (!$apiKey) {
//         return response()->json(['error' => 'Ably API key not set'], 500);
//     }

//     $ably = new AblyRest($apiKey);

//     try {
//         $tokenRequest = $ably->auth->requestToken();
//         return response()->json([
//             'token' => $tokenRequest->token,
//             'expires' => $tokenRequest->expires,
//             'issued' => $tokenRequest->issued,
//             'capability' => json_decode($tokenRequest->capability, true),
//         ], 200, [], JSON_UNESCAPED_SLASHES);

//     } catch (\Exception $e) {
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// });

// Route::post('/broadcasting/auth', function(Request $request) {
//     $token = $request->bearerToken();
//     if (!$token) return response()->json(['message' => 'Unauthorized'], 401);

//     $pat = PersonalAccessToken::findToken($token);
//     $user = $pat?->tokenable;
//     if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

//     Auth::login($user);

//     try {
//         $ably = new AblyRest(env('ABLY_KEY'));

//         $tokenParams = new TokenParams(['clientId' => (string) $user->id]);

//         $tokenRequest = $ably->auth->createTokenRequest($tokenParams);

//         return response()->json([
//             'tokenRequest' => $tokenRequest
//         ]);
//     } catch (\Throwable $e) {
//         return response()->json([
//             'message' => 'Ably token generation failed',
//             'error' => $e->getMessage(),
//         ], 500);
//     }
// });

Route::post('/ably-auth', function () {
    $user = Auth::guard('api')->user();
    if (!$user) {
        return response()->json(['message' => 'Unauthorized'], 401);
    }

    $ably = new AblyRest(env('ABLY_KEY'));

    $tokenRequest = $ably->auth->createTokenRequest([
        'clientId' => (string) $user->id, 
    ]);

    return response()->json($tokenRequest);
});