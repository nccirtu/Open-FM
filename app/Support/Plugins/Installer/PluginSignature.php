<?php

namespace App\Support\Plugins\Installer;

use SodiumException;

final class PluginSignature
{
    public static function verify(string $zipPath, string $signature, string $publicKey): bool
    {
        $signatureBytes = base64_decode(trim($signature), true);
        $keyBytes = base64_decode(trim($publicKey), true);

        if ($signatureBytes === false || $keyBytes === false
            || strlen($signatureBytes) !== SODIUM_CRYPTO_SIGN_BYTES
            || strlen($keyBytes) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            return false;
        }

        try {
            return sodium_crypto_sign_verify_detached($signatureBytes, (string) file_get_contents($zipPath), $keyBytes);
        } catch (SodiumException) {
            return false;
        }
    }

    public static function sign(string $zipPath, string $secretKey): string
    {
        return base64_encode(sodium_crypto_sign_detached((string) file_get_contents($zipPath), (string) base64_decode(trim($secretKey), true)));
    }

    /**
     * @return array{public: string, secret: string}
     */
    public static function generateKeyPair(): array
    {
        $pair = sodium_crypto_sign_keypair();

        return [
            'public' => base64_encode(sodium_crypto_sign_publickey($pair)),
            'secret' => base64_encode(sodium_crypto_sign_secretkey($pair)),
        ];
    }
}
