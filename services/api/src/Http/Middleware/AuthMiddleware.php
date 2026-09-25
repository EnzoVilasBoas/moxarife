<?php

declare(strict_types=1);

namespace Moxarife\Api\Http\Middleware;

use Firebase\JWT\Key;
use Firebase\JWT\JWT;
use Moxarife\Api\Config\Environment;
use Moxarife\Api\Repositories\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Environment $environment,
        private readonly UserRepository $users
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $header = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $this->unauthorized();
        }

        try {
            $claims = (array) JWT::decode(
                $matches[1],
                new Key($this->environment->get('JWT_SECRET'), 'HS256')
            );
            if (($claims['iss'] ?? null) !== $this->environment->get('JWT_ISSUER', 'moxarife-api')) {
                return $this->unauthorized();
            }
            $userId = filter_var($claims['sub'] ?? null, FILTER_VALIDATE_INT);
            $user = $userId ? $this->users->findActiveById((int) $userId) : null;
            if ($user === null) {
                return $this->unauthorized();
            }

            $request = $request->withAttribute('user', $user);
        } catch (Throwable) {
            return $this->unauthorized();
        }

        return $handler->handle($request);
    }

    private function unauthorized(): ResponseInterface
    {
        $response = new \Slim\Psr7\Response(401);
        $response->getBody()->write(json_encode([
            'error' => [
                'code' => 'unauthorized',
                'message' => 'Autenticação necessária.',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
