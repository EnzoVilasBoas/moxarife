<?php

declare(strict_types=1);

namespace Moxarife\Api\Repositories;

use PDO;

final class InventoryRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /** @return array{items: list<array<string, mixed>>, total: int} */
    public function listItems(int $page, int $perPage, string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;
        $where = 'i.active = 1';
        $params = [];

        if ($search !== '') {
            $where .= ' AND (i.code LIKE :search OR i.name LIKE :search OR i.description LIKE :search)';
            $params['search'] = '%' . $search . '%';
        }

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM inventory_items i WHERE $where");
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $statement = $this->pdo->prepare(
            "SELECT i.id, i.code, i.name, i.unit, i.description, i.minimum_quantity,
                    COALESCE(s.quantity, 0) AS quantity,
                    i.created_at, i.updated_at
             FROM inventory_items i
             LEFT JOIN inventory_stock s ON s.item_id = i.id
             WHERE $where
             ORDER BY i.name ASC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return [
            'items' => $statement->fetchAll(),
            'total' => $total,
        ];
    }

    /** @return array<string, mixed>|null */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT i.id, i.code, i.name, i.unit, i.description, i.minimum_quantity,
                    COALESCE(s.quantity, 0) AS quantity,
                    i.created_at, i.updated_at
             FROM inventory_items i
             LEFT JOIN inventory_stock s ON s.item_id = i.id
             WHERE i.id = :id AND i.active = 1'
        );
        $statement->execute(['id' => $id]);
        $item = $statement->fetch();

        return $item === false ? null : $item;
    }

    /** @param array{code: string, name: string, unit: string, description?: string|null, minimum_quantity: float} $data */
    public function create(array $data, int $userId): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO inventory_items (code, name, unit, description, minimum_quantity, created_by)
             VALUES (:code, :name, :unit, :description, :minimum_quantity, :created_by)'
        );
        $statement->execute([
            'code' => $data['code'],
            'name' => $data['name'],
            'unit' => $data['unit'],
            'description' => $data['description'] ?? null,
            'minimum_quantity' => $data['minimum_quantity'],
            'created_by' => $userId,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): bool
    {
        $allowed = ['code', 'name', 'unit', 'description', 'minimum_quantity'];
        $sets = [];
        $params = ['id' => $id];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $sets[] = "$field = :$field";
                $params[$field] = $data[$field];
            }
        }

        if ($sets === []) {
            return false;
        }

        $statement = $this->pdo->prepare(
            'UPDATE inventory_items SET ' . implode(', ', $sets) . ' WHERE id = :id AND active = 1'
        );
        $statement->execute($params);

        return $statement->rowCount() > 0;
    }

    public function deactivate(int $id): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE inventory_items SET active = 0 WHERE id = :id AND active = 1'
        );
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    /** @return list<array<string, mixed>> */
    public function listMovements(int $itemId, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $statement = $this->pdo->prepare(
            'SELECT m.id, m.item_id, m.type, m.quantity, m.note, m.created_at,
                    u.id AS user_id, u.name AS user_name
             FROM inventory_movements m
             INNER JOIN users u ON u.id = m.user_id
             WHERE m.item_id = :item_id
             ORDER BY m.created_at DESC, m.id DESC
             LIMIT :limit OFFSET :offset'
        );
        $statement->bindValue(':item_id', $itemId, PDO::PARAM_INT);
        $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }

    /** @param array{type: string, quantity: float, note?: string|null} $data */
    public function createMovement(int $itemId, int $userId, array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO inventory_movements (item_id, user_id, type, quantity, note)
             VALUES (:item_id, :user_id, :type, :quantity, :note)'
        );
        $statement->execute([
            'item_id' => $itemId,
            'user_id' => $userId,
            'type' => $data['type'],
            'quantity' => $data['quantity'],
            'note' => $data['note'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }
}
