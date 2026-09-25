<?php

declare(strict_types=1);

namespace Moxarife\Api\Controllers;

use Moxarife\Api\Http\ApiException;
use Moxarife\Api\Http\JsonResponse;
use Moxarife\Api\Services\InventoryService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class InventoryController
{
    public function __construct(private readonly InventoryService $inventory)
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 20)));
        $search = trim((string) ($query['search'] ?? ''));

        return JsonResponse::data($response, $this->inventory->listItems($page, $perPage, $search));
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return JsonResponse::data($response, $this->inventory->getItem($this->id($args)));
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');
        $userId = is_array($user) ? (int) ($user['id'] ?? 0) : 0;
        if ($userId < 1) {
            throw new ApiException(401, 'unauthorized', 'Autenticação necessária.');
        }

        return JsonResponse::created($response, $this->inventory->createItem($this->body($request), $userId));
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        return JsonResponse::data(
            $response,
            $this->inventory->updateItem($this->id($args), $this->body($request))
        );
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $this->inventory->deleteItem($this->id($args));
        return JsonResponse::empty($response);
    }

    public function movements(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $query = $request->getQueryParams();
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = min(100, max(1, (int) ($query['per_page'] ?? 20)));

        return JsonResponse::data(
            $response,
            $this->inventory->listMovements($this->id($args), $page, $perPage)
        );
    }

    public function createMovement(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');
        $userId = is_array($user) ? (int) ($user['id'] ?? 0) : 0;
        if ($userId < 1) {
            throw new ApiException(401, 'unauthorized', 'Autenticação necessária.');
        }

        $body = $this->body($request);
        $itemId = filter_var($body['item_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$itemId) {
            throw new ApiException(422, 'validation_error', 'Informe o item da movimentação.', ['field' => 'item_id']);
        }

        $id = $this->inventory->createMovement((int) $itemId, $userId, $body);
        return JsonResponse::created($response, ['id' => $id]);
    }

    /** @param array<string, mixed> $args */
    private function id(array $args): int
    {
        $id = filter_var($args['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            throw new ApiException(400, 'invalid_id', 'Identificador inválido.');
        }

        return (int) $id;
    }

    /** @return array<string, mixed> */
    private function body(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }
}
