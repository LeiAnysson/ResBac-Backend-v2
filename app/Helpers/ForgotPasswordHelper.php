<?php

namespace App\Helpers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

if (!function_exists('sendPasswordResetEmail')) {
    /**
     *
     * @param \App\Models\User $user
     * @param int $minutes
     * @return void
     */
    function sendPasswordResetEmail($user, $minutes = 30)
    {
        $token = Password::createToken($user);

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000') . "/reset-password?token={$token}&email={$user->email}";

        Mail::send('emails.password_reset', ['url' => $frontendUrl, 'user' => $user], function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Reset Your Password');
        });
    }
}
