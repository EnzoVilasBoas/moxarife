<?php

declare(strict_types=1);

namespace Moxarife\Api\Services;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use Moxarife\Api\Config\Environment;
use Moxarife\Api\Repositories\RefreshTokenRepository;
use Moxarife\Api\Repositories\UserRepository;

final class TokenService
{
    public function __construct(
        private readonly Environment $environment,
        private readonly UserRepository $users,
        private readonly RefreshTokenRepository $refreshTokens
    ) {
    }

    /** @param array<string, mixed> $user */
    public function issue(array $user): array
    {
        $now = new DateTimeImmutable();
        $accessExpiresAt = $now->modify('+' . $this->environment->int('JWT_TTL', 900) . ' seconds');
        $refreshExpiresAt = $now->modify('+' . $this->environment->int('REFRESH_TOKEN_TTL', 2592000) . ' seconds');
        $refreshToken = bin2hex(random_bytes(48));

        $this->refreshTokens->create(
            (int) $user['id'],
            hash('sha256', $refreshToken),
            $refreshExpiresAt
        );

        $accessToken = JWT::encode([
            'iss' => $this->environment->get('JWT_ISSUER', 'moxarife-api'),
            'sub' => (string) $user['id'],
            'iat' => $now->getTimestamp(),
            'exp' => $accessExpiresAt->getTimestamp(),
            'jti' => bin2hex(random_bytes(16)),
        ], $this->environment->get('JWT_SECRET'), 'HS256');

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->environment->int('JWT_TTL', 900),
            'user' => [
                'id' => (int) $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public function userForRefresh(string $refreshToken): ?array
    {
        $record = $this->refreshTokens->findValidByHash(hash('sha256', $refreshToken));
        if ($record === null) {
            return null;
        }

        $user = $this->users->findActiveById((int) $record['user_id']);
        if ($user === null) {
            $this->refreshTokens->revokeById((int) $record['id']);
            return null;
        }

        $this->refreshTokens->revokeById((int) $record['id']);
        return $user;
    }

    public function revokeRefreshToken(string $refreshToken): bool
    {
        return $this->refreshTokens->revokeByHash(hash('sha256', $refreshToken));
    }
}
