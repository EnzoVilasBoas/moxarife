<?php

declare(strict_types=1);

namespace Moxarife\Api\Repositories;

use DateTimeImmutable;
use PDO;

final class RefreshTokenRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function create(int $userId, string $tokenHash, DateTimeImmutable $expiresAt): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO refresh_tokens (user_id, token_hash, expires_at) VALUES (:user_id, :token_hash, :expires_at)'
        );
        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    /** @return array<string, mixed>|null */
    /** @return array<string, mixed>|null */
    public function findValidByHash(string $tokenHash): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, user_id, expires_at FROM refresh_tokens
             WHERE token_hash = :token_hash AND revoked_at IS NULL AND expires_at > UTC_TIMESTAMP()
             LIMIT 1'
        );
        $statement->execute(['token_hash' => $tokenHash]);
        $token = $statement->fetch();

        return $token === false ? null : $token;
    }

    public function revokeByHash(string $tokenHash): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE refresh_tokens SET revoked_at = UTC_TIMESTAMP()
             WHERE token_hash = :token_hash AND revoked_at IS NULL'
        );
        $statement->execute(['token_hash' => $tokenHash]);

        return $statement->rowCount() === 1;
    }

    public function revokeById(int $id): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE refresh_tokens SET revoked_at = UTC_TIMESTAMP()
             WHERE id = :id AND revoked_at IS NULL'
        );
        $statement->execute(['id' => $id]);

        return $statement->rowCount() === 1;
    }
}
