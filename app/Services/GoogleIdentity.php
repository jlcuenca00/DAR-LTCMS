<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

class GoogleIdentity
{
    private const CERTIFICATES_URL = 'https://www.googleapis.com/oauth2/v1/certs';
    private const CERTIFICATES_CACHE_KEY = 'google_identity_certificates';

    public function verify(string $credential): ?array
    {
        $clientId = (string) config('services.google.client_id');

        if ($clientId === '') {
            return null;
        }

        $parts = explode('.', $credential);

        if (count($parts) !== 3) {
            return null;
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = $this->decodeJson($encodedHeader);
        $payload = $this->decodeJson($encodedPayload);
        $signature = $this->decodeBase64Url($encodedSignature);

        if (
            ! $header ||
            ! $payload ||
            $signature === null ||
            ($header['alg'] ?? null) !== 'RS256' ||
            ! is_string($header['kid'] ?? null)
        ) {
            return null;
        }

        $certificates = $this->certificates();
        $certificate = $certificates[$header['kid']] ?? null;

        if (! is_string($certificate)) {
            Cache::forget(self::CERTIFICATES_CACHE_KEY);
            $certificates = $this->certificates();
            $certificate = $certificates[$header['kid']] ?? null;
        }

        if (
            ! is_string($certificate) ||
            openssl_verify(
                $encodedHeader.'.'.$encodedPayload,
                $signature,
                $certificate,
                OPENSSL_ALGO_SHA256
            ) !== 1
        ) {
            return null;
        }

        $audience = $payload['aud'] ?? null;
        $validAudience = is_array($audience)
            ? in_array($clientId, $audience, true)
            : is_string($audience) && hash_equals($clientId, $audience);

        if (
            ! $validAudience ||
            ! in_array($payload['iss'] ?? null, ['accounts.google.com', 'https://accounts.google.com'], true) ||
            ! is_numeric($payload['exp'] ?? null) ||
            (int) $payload['exp'] <= time() ||
            ! is_string($payload['sub'] ?? null) ||
            $payload['sub'] === ''
        ) {
            return null;
        }

        return $payload;
    }

    private function certificates(): array
    {
        return Cache::remember(self::CERTIFICATES_CACHE_KEY, now()->addHours(4), function (): array {
            try {
                $response = Http::acceptJson()->timeout(10)->get(self::CERTIFICATES_URL);

                if (! $response->successful()) {
                    return [];
                }

                $certificates = $response->json();

                return is_array($certificates) ? $certificates : [];
            } catch (Throwable) {
                return [];
            }
        });
    }

    private function decodeJson(string $value): ?array
    {
        $decoded = $this->decodeBase64Url($value);

        if ($decoded === null) {
            return null;
        }

        try {
            $data = json_decode($decoded, true, 16, JSON_THROW_ON_ERROR);

            return is_array($data) ? $data : null;
        } catch (JsonException) {
            return null;
        }
    }

    private function decodeBase64Url(string $value): ?string
    {
        $padding = strlen($value) % 4;

        if ($padding !== 0) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
