<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class CryptoHelper
{
    public static function decryptPassword($encryptedPassword, $keyIndex)
    {
        $key = self::getKey($keyIndex);
        if (!$key) {
            throw new \Exception("Invalid key index or missing key in .env");
        }

        $key = mb_convert_encoding($key, 'UTF-8');

        $encryptedData = base64_decode($encryptedPassword);

        $iv = substr($encryptedData, 0, 16);
        $ciphertext = substr($encryptedData, 16);

        $decrypted = openssl_decrypt(
            $ciphertext,
            'AES-256-CBC',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decrypted;
    }

    private static function getKey($num)
    {
        $num = (string) $num;
        $key = null;
        switch ($num) {
            case "1":
                $key = env("CRYPTOJS_SECRET_KEY_1");
                break;
            case "2":
                $key = env("CRYPTOJS_SECRET_KEY_2");
                break;
            case "3":
                $key = env("CRYPTOJS_SECRET_KEY_3");
                break;
        }

        if (!$key) {
            Log::error("CryptoHelper: Missing key for index {$num}");
        }

        return $key;
    }
}
