<?php

declare(strict_types=1);

namespace Moxarife\Api\Http;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

final class JsonResponse
{
    /** @param array<string, mixed> $data */
    public static function data(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode(
            ['data' => $data],
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        ));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /** @param array<string, mixed> $data */
    public static function created(ResponseInterface $response, array $data): ResponseInterface
    {
        return self::data($response, $data, 201);
    }

    public static function empty(ResponseInterface $response, int $status = 204): ResponseInterface
    {
        return $response->withStatus($status);
    }

    public static function fromException(ResponseInterface $response, ApiException $exception): ResponseInterface
    {
        $body = [
            'error' => [
                'code' => $exception->errorCode,
                'message' => $exception->getMessage(),
            ],
        ];

        if ($exception->details !== []) {
            $body['error']['details'] = $exception->details;
        }

        $response->getBody()->write(json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response
            ->withStatus($exception->status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
