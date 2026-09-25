<?php

declare(strict_types=1);

namespace Moxarife\Api\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Throwable;

final class JsonBodyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if (str_contains(strtolower($contentType), 'application/json') && $request->getBody()->getSize() > 0) {
            try {
                $request->getBody()->rewind();
                $payload = json_decode((string) $request->getBody(), true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($payload)) {
                    throw new \InvalidArgumentException('O corpo JSON deve ser um objeto.');
                }
                $request = $request->withParsedBody($payload);
            } catch (Throwable) {
                $response = (new Response())->withStatus(400);
                $response->getBody()->write(json_encode([
                    'error' => [
                        'code' => 'invalid_json',
                        'message' => 'O corpo da requisição não contém JSON válido.',
                    ],
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
            }
        }

        return $handler->handle($request);
    }
}
