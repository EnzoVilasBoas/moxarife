<?php

declare(strict_types=1);

namespace Moxarife\Api\Http\Middleware;

use Moxarife\Api\Http\ApiException;
use Moxarife\Api\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpException;
use Slim\Psr7\Response;
use Throwable;

final class JsonErrorMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ApiException $exception) {
            return JsonResponse::fromException(new Response(), $exception);
        } catch (HttpException $exception) {
            $status = $exception->getCode() >= 400 && $exception->getCode() < 600
                ? $exception->getCode()
                : 500;
            $apiException = new ApiException(
                $status,
                $status === 404 ? 'not_found' : 'http_error',
                $status === 404 ? 'Recurso não encontrado.' : 'Não foi possível processar a requisição.'
            );
            return JsonResponse::fromException(new Response(), $apiException);
        } catch (Throwable $exception) {
            error_log(sprintf(
                '[moxarife-api] %s: %s in %s:%d',
                $exception::class,
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine()
            ));

            return JsonResponse::fromException(
                new Response(),
                new ApiException(500, 'internal_error', 'Ocorreu um erro interno.')
            );
        }
    }
}
