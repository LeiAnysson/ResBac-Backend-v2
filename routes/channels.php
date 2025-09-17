<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::routes(['middleware' => ['auth:sanctum']]); 

Broadcast::channel('dispatcher-channel', function ($user) {
    return $user && $user->role_id === 2;
});

Broadcast::channel('resident.{id}', function ($user, $id) {
    return $user && (int) $user->id === (int) $id;
});
