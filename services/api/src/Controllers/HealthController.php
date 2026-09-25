<?php

declare(strict_types=1);

namespace Moxarife\Api\Controllers;

use Moxarife\Api\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class HealthController
{
    public function __construct(private readonly \PDO $pdo)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            $this->pdo->query('SELECT 1');
            $database = 'ok';
        } catch (Throwable) {
            $database = 'unavailable';
        }

        return JsonResponse::data($response, [
            'service' => 'moxarife-api',
            'status' => $database === 'ok' ? 'ok' : 'degraded',
            'database' => $database,
            'timestamp' => gmdate('c'),
        ]);
    }
}
