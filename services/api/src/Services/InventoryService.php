<?php

declare(strict_types=1);

namespace Moxarife\Api\Services;

use Moxarife\Api\Http\ApiException;
use Moxarife\Api\Repositories\InventoryRepository;

final class InventoryService
{
    public function __construct(private readonly InventoryRepository $items)
    {
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    public function listItems(int $page, int $perPage, string $search): array
    {
        return $this->items->listItems($page, $perPage, $search);
    }

    /** @return array<string, mixed> */
    public function getItem(int $id): array
    {
        $item = $this->items->findById($id);
        if ($item === null) {
            throw new ApiException(404, 'item_not_found', 'Item não encontrado.');
        }

        return $item;
    }

    /** @param array<string, mixed> $data */
    public function createItem(array $data, int $userId): array
    {
        $code = $this->text($data['code'] ?? '', 'code', 80);
        $name = $this->text($data['name'] ?? '', 'name', 180);
        $unit = $this->text($data['unit'] ?? '', 'unit', 20);
        $description = isset($data['description'])
            ? $this->text($data['description'], 'description', 500, false)
            : null;
        $minimumQuantity = $this->decimal($data['minimum_quantity'] ?? 0, 'minimum_quantity');

        try {
            $id = $this->items->create([
                'code' => $code,
                'name' => $name,
                'unit' => $unit,
                'description' => $description !== '' ? $description : null,
                'minimum_quantity' => $minimumQuantity,
            ], $userId);
        } catch (\PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                throw new ApiException(409, 'item_code_exists', 'Já existe um item com este código.');
            }
            throw $exception;
        }

        return $this->getItem($id);
    }

    /** @param array<string, mixed> $data */
    public function updateItem(int $id, array $data): array
    {
        $this->getItem($id);
        $allowed = [];
        foreach (['code', 'name', 'unit', 'description', 'minimum_quantity'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $allowed[$field] = $field === 'minimum_quantity'
                ? $this->decimal($data[$field], $field)
                : $this->text($data[$field], $field, match ($field) {
                    'code' => 80,
                    'name' => 180,
                    'unit' => 20,
                    default => 500,
                }, $field !== 'description');
        }

        if ($allowed === [] || !$this->items->update($id, $allowed)) {
            throw new ApiException(422, 'validation_error', 'Nenhum campo válido foi informado.');
        }

        return $this->getItem($id);
    }

    public function deleteItem(int $id): void
    {
        $this->getItem($id);
        if (!$this->items->deactivate($id)) {
            throw new ApiException(409, 'item_not_found', 'Item não encontrado.');
        }
    }

    /** @return list<array<string, mixed>> */
    public function listMovements(int $itemId, int $page, int $perPage): array
    {
        $this->getItem($itemId);
        return $this->items->listMovements($itemId, $page, $perPage);
    }

    /** @param array<string, mixed> $data */
    public function createMovement(int $itemId, int $userId, array $data): int
    {
        $this->getItem($itemId);
        $type = (string) ($data['type'] ?? '');
        if (!in_array($type, ['entry', 'exit', 'adjustment'], true)) {
            throw new ApiException(422, 'validation_error', 'Tipo de movimentação inválido.', ['field' => 'type']);
        }

        $quantity = $this->decimal($data['quantity'] ?? null, 'quantity');
        if ($quantity <= 0) {
            throw new ApiException(422, 'validation_error', 'A quantidade deve ser maior que zero.', ['field' => 'quantity']);
        }

        $note = isset($data['note']) ? trim((string) $data['note']) : null;
        return $this->items->createMovement($itemId, $userId, [
            'type' => $type,
            'quantity' => $quantity,
            'note' => $note !== '' ? $note : null,
        ]);
    }

    private function decimal(mixed $value, string $field): float
    {
        if (!is_numeric($value) || (float) $value < 0) {
            throw new ApiException(422, 'validation_error', 'Informe uma quantidade válida.', ['field' => $field]);
        }

        return round((float) $value, 3);
    }

    private function text(mixed $value, string $field, int $maxLength, bool $required = true): string
    {
        $text = trim((string) $value);
        if (($required && $text === '') || mb_strlen($text) > $maxLength) {
            throw new ApiException(422, 'validation_error', 'Informe um valor válido.', ['field' => $field]);
        }

        return $text;
    }
}
