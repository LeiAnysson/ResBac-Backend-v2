<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Helpers\CryptoHelper;

class RequestDecryptionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {   
        Log::info('RequestDecryptionMiddleware triggered via alias.');
        if ($request->is('api/login') && $request->has('auth')) {
            $parts = explode('|', $request->input('auth'), 2);

            if (count($parts) !== 2) {
                return response()->json(['message' => 'Invalid auth format'], 400);
            }

            [$email, $blended] = $parts;

            $keyName = substr($blended, 0, 1);
            $encryptedPassword = substr($blended, 1);

            $decryptedPassword = CryptoHelper::decryptPassword($encryptedPassword, $keyName);

            if (!$decryptedPassword) {
                return response()->json(['message' => 'Password decryption failed'], 400);
            }

            $request->merge([
                'email' => $email,
                'password' => $decryptedPassword
            ]);
        }

        return $next($request);
    }
}