<?php

declare(strict_types=1);

namespace Moxarife\Api\Controllers;

use Moxarife\Api\Http\ApiException;
use Moxarife\Api\Http\JsonResponse;
use Moxarife\Api\Services\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->body($request);
        $email = trim((string) ($body['email'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            throw new ApiException(422, 'validation_error', 'Informe e-mail e senha válidos.');
        }

        return JsonResponse::data($response, $this->auth->login($email, $password));
    }

    public function refresh(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $refreshToken = trim((string) ($this->body($request)['refresh_token'] ?? ''));
        if ($refreshToken === '') {
            throw new ApiException(422, 'validation_error', 'Informe o refresh token.');
        }

        return JsonResponse::data($response, $this->auth->refresh($refreshToken));
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $refreshToken = trim((string) ($this->body($request)['refresh_token'] ?? ''));
        if ($refreshToken !== '') {
            $this->auth->logout($refreshToken);
        }

        return JsonResponse::empty($response);
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');
        return JsonResponse::data($response, is_array($user) ? $user : []);
    }

    /** @return array<string, mixed> */
    private function body(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }
}
