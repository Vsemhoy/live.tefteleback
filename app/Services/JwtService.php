<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private string $secretKey;

    private string $algorithm = 'HS256';

    public function __construct()
    {
        $this->secretKey = env('JWT_SECRET', 'your_fallback_secret');
    }

    public function generateToken(array $payload, int $expiry = 3600): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));

            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function formatTokenPayload(array $payload): array
    {
        return [
            'issued_at' => date('Y-m-d H:i:s', $payload['iat']),
            'expires_at' => date('Y-m-d H:i:s', $payload['exp']),
            'valid_for_seconds' => $payload['exp'] - time(),
        ];
    }
}
