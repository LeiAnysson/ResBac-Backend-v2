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
