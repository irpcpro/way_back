<?php

namespace App\Services;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Minimal Web Push sender (VAPID RFC 8292 + aes128gcm payload encryption RFC 8291) using only ext-openssl,
 * so no extra composer package is needed.
 */
class WebPush
{
    private const SPKI_P256_PREFIX = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

    /**
     * Send a JSON payload to one subscription. Returns false when the subscription is gone (404/410) and was deleted.
     */
    public static function send(PushSubscription $subscription, array $payload, int $ttl = 86400): bool
    {
        $endpoint = $subscription->endpoint;
        $body = self::encrypt(
            json_encode($payload, JSON_UNESCAPED_UNICODE),
            self::base64UrlDecode($subscription->p256dh),
            self::base64UrlDecode($subscription->auth)
        );

        $parts = parse_url($endpoint);
        $audience = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

        try {
            $response = Http::timeout(5)->connectTimeout(3)
                ->withHeaders([
                    'Authorization' => 'vapid t=' . self::vapidJwt($audience) . ', k=' . config('services.webpush.public_key'),
                    'Content-Encoding' => 'aes128gcm',
                    'Content-Type' => 'application/octet-stream',
                    'TTL' => (string) $ttl,
                    'Urgency' => 'high',
                ])
                ->withBody($body, 'application/octet-stream')
                ->post($endpoint);
        } catch (\Throwable $exception) {
            Log::warning('web push request failed', ['endpoint_host' => $parts['host'] ?? null, 'error' => $exception->getMessage()]);
            return true;
        }

        if ($response->status() === 404 || $response->status() === 410) {
            $subscription->delete();
            return false;
        }

        if (!$response->successful()) {
            Log::warning('web push rejected', ['endpoint_host' => $parts['host'] ?? null, 'status' => $response->status(), 'body' => $response->body()]);
        }

        return true;
    }

    /**
     * Generate a VAPID key pair as base64url strings (raw uncompressed public key, raw private scalar).
     */
    public static function generateVapidKeys(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $details = openssl_pkey_get_details($key);

        return [
            'public_key' => self::base64UrlEncode(self::rawPublicKey($details)),
            'private_key' => self::base64UrlEncode(str_pad($details['ec']['d'], 32, "\0", STR_PAD_LEFT)),
        ];
    }

    private static function vapidJwt(string $audience): string
    {
        $header = self::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
        $claims = self::base64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => time() + 12 * 3600,
            'sub' => config('services.webpush.subject'),
        ]));
        $input = $header . '.' . $claims;

        $privateKey = openssl_pkey_get_private(self::vapidPrivateKeyPem());
        if (!openssl_sign($input, $derSignature, $privateKey, OPENSSL_ALGO_SHA256))
            throw new RuntimeException('could not sign VAPID token');

        return $input . '.' . self::base64UrlEncode(self::derToRawSignature($derSignature));
    }

    private static function encrypt(string $payload, string $userPublicKey, string $authSecret): string
    {
        // ephemeral server key for this message
        $serverKey = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $serverPublicKey = self::rawPublicKey(openssl_pkey_get_details($serverKey));

        $userKey = openssl_pkey_get_public(self::publicKeyPem($userPublicKey));
        $sharedSecret = openssl_pkey_derive($userKey, $serverKey, 32);
        if ($sharedSecret === false)
            throw new RuntimeException('ECDH failed');

        $prkKey = hash_hmac('sha256', $sharedSecret, $authSecret, true);
        $ikm = hash_hmac('sha256', "WebPush: info\0" . $userPublicKey . $serverPublicKey . "\x01", $prkKey, true);

        $salt = random_bytes(16);
        $prk = hash_hmac('sha256', $ikm, $salt, true);
        $cek = substr(hash_hmac('sha256', "Content-Encoding: aes128gcm\0\x01", $prk, true), 0, 16);
        $nonce = substr(hash_hmac('sha256', "Content-Encoding: nonce\0\x01", $prk, true), 0, 12);

        // single record: payload + 0x02 delimiter
        $cipherText = openssl_encrypt($payload . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);

        return $salt . pack('N', 4096) . chr(strlen($serverPublicKey)) . $serverPublicKey . $cipherText . $tag;
    }

    private static function rawPublicKey(array $details): string
    {
        return "\x04"
            . str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT)
            . str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT);
    }

    private static function publicKeyPem(string $rawPublicKey): string
    {
        $der = hex2bin(self::SPKI_P256_PREFIX) . $rawPublicKey;
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private static function vapidPrivateKeyPem(): string
    {
        $d = self::base64UrlDecode(config('services.webpush.private_key'));
        $publicKey = self::base64UrlDecode(config('services.webpush.public_key'));

        // SEC1 ECPrivateKey for prime256v1
        $der = hex2bin('30770201010420') . $d
            . hex2bin('a00a06082a8648ce3d030107a144034200') . $publicKey;

        return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
    }

    private static function derToRawSignature(string $der): string
    {
        // SEQUENCE { INTEGER r, INTEGER s }
        $offset = 2;
        if (ord($der[1]) & 0x80)
            $offset += ord($der[1]) & 0x7f;

        $parts = [];
        for ($i = 0; $i < 2; $i++) {
            $length = ord($der[$offset + 1]);
            $value = ltrim(substr($der, $offset + 2, $length), "\0");
            $parts[] = str_pad($value, 32, "\0", STR_PAD_LEFT);
            $offset += 2 + $length;
        }

        return $parts[0] . $parts[1];
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4));
    }
}
