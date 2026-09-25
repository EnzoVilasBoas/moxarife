<?php

declare(strict_types=1);

namespace Moxarife\Api\Http\Middleware;

use Moxarife\Api\Config\Environment;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly Environment $environment)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');
        $allowed = array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', $this->environment->get('CORS_ORIGINS', ''))
        )));
        $response = $handler->handle($request);

        if ($origin !== '' && in_array($origin, $allowed, true)) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Vary', 'Origin')
                ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PATCH, DELETE, OPTIONS');
        }

        if ($request->getMethod() === 'OPTIONS') {
            $response = $response->withStatus(204);
        }

        return $response;
    }
}
