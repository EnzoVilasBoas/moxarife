<?php

declare(strict_types=1);

use Moxarife\Api\Config\Environment;
use Moxarife\Api\Database\Connection;
use Moxarife\Api\Repositories\UserRepository;

require dirname(__DIR__) . '/vendor/autoload.php';

if ($argc < 3) {
    fwrite(STDERR, "Uso: php bin/create-user.php <email> <nome> [admin|operator]\n");
    exit(1);
}

$email = trim((string) $argv[1]);
$name = trim((string) $argv[2]);
$role = $argv[3] ?? 'operator';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || !in_array($role, ['admin', 'operator'], true)) {
    fwrite(STDERR, "Email, nome ou papel inválido.\n");
    exit(1);
}

$password = getenv('MOXARIFE_INITIAL_PASSWORD');
if (!is_string($password) || $password === '') {
    fwrite(STDOUT, "Senha inicial: ");
    $password = trim((string) fgets(STDIN));
}

if (strlen($password) < 8) {
    fwrite(STDERR, "A senha deve ter pelo menos 8 caracteres.\n");
    exit(1);
}

try {
    $environment = Environment::fromSources(dirname(__DIR__) . '/.env');
    $pdo = Connection::fromEnvironment($environment);
    $users = new UserRepository($pdo);

    if ($users->findByEmail($email) !== null) {
        fwrite(STDERR, "Usuário já existe.\n");
        exit(1);
    }

    $users->create([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_ARGON2ID),
        'role' => $role,
    ]);

    fwrite(STDOUT, "Usuário criado com sucesso.\n");
} catch (Throwable $exception) {
    fwrite(STDERR, "Erro ao criar usuário: {$exception->getMessage()}\n");
    exit(1);
}
