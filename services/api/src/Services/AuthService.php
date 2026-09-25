<?php

declare(strict_types=1);

namespace Moxarife\Api\Services;

use Moxarife\Api\Http\ApiException;
use Moxarife\Api\Repositories\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly TokenService $tokens
    ) {
    }

    /** @return array<string, mixed> */
    public function login(string $email, string $password): array
    {
        $user = $this->users->findByEmail($email);
        if ($user === null || !(bool) $user['active'] || !password_verify($password, $user['password_hash'])) {
            throw new ApiException(401, 'invalid_credentials', 'E-mail ou senha inválidos.');
        }

        return $this->tokens->issue($user);
    }

    /** @return array<string, mixed> */
    public function refresh(string $refreshToken): array
    {
        $user = $this->tokens->userForRefresh($refreshToken);
        if ($user === null) {
            throw new ApiException(401, 'invalid_refresh_token', 'Refresh token inválido ou expirado.');
        }

        return $this->tokens->issue($user);
    }

    public function logout(string $refreshToken): void
    {
        $this->tokens->revokeRefreshToken($refreshToken);
    }
}
