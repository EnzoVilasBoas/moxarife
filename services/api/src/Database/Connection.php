<?php

declare(strict_types=1);

namespace Moxarife\Api\Database;

use Moxarife\Api\Config\Environment;
use PDO;

final class Connection
{
    public static function fromEnvironment(Environment $environment): PDO
    {
        $host = $environment->get('DB_HOST');
        $port = $environment->int('DB_PORT', 3306);
        $database = $environment->get('DB_NAME');
        $username = $environment->get('DB_USER');
        $password = $environment->get('DB_PASSWORD');

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $database
        );

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
