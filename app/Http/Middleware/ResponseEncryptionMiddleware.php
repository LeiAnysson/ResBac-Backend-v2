<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseEncryptionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Processed-By-AdminMiddleware', 'true');

        if ($response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'application/json')) {
            $originalContent = json_decode($response->getContent(), true);

            if (is_array($originalContent) && isset($originalContent['encryptedPassword'], $originalContent['secretKeyName'])) {
                
                $blended = $originalContent['secretKeyName'] . $originalContent['encryptedPassword'];

                $originalContent['encryptedPassword'] = $blended;
                unset($originalContent['secretKeyName']);

                $response->setContent(json_encode($originalContent));
            }
        }
        return $response;
    }

    public function encryptData($plainText)
    {
        $data = is_array($plainText) ? json_encode($plainText) : $plainText;
        $key = env("CRYPTOJS_SECRET_KEY");

        $iv = openssl_random_pseudo_bytes(16);

        $ciphertext = openssl_encrypt(
            $data,
            env("CIPHER_ALGO"),
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        $encryptedData = base64_encode($iv . $ciphertext);

        return $encryptedData;
    }
}
