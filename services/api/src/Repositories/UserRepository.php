<?php

declare(strict_types=1);

namespace Moxarife\Api\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array<string, mixed>|null */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, password_hash, role, active FROM users WHERE email = :email LIMIT 1'
        );
        $statement->execute(['email' => mb_strtolower(trim($email))]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    /** @return array<string, mixed>|null */
    public function findActiveById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, name, email, role, active FROM users WHERE id = :id AND active = 1 LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return $user === false ? null : $user;
    }

    /** @param array{name: string, email: string, password_hash: string, role: string} $data */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
        );
        $statement->execute([
            'name' => trim($data['name']),
            'email' => mb_strtolower(trim($data['email'])),
            'password_hash' => $data['password_hash'],
            'role' => $data['role'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
