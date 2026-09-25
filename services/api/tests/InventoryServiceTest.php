<?php

declare(strict_types=1);

namespace Moxarife\Api\Tests;

use Moxarife\Api\Http\ApiException;
use Moxarife\Api\Repositories\InventoryRepository;
use Moxarife\Api\Services\InventoryService;
use PHPUnit\Framework\TestCase;

final class InventoryServiceTest extends TestCase
{
    public function testCreateItemRejectsEmptyCode(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $service = new InventoryService(new InventoryRepository($pdo));

        $this->expectException(ApiException::class);
        $service->createItem([
            'code' => '',
            'name' => 'Item',
            'unit' => 'un',
        ], 1);
    }

    public function testCreateMovementRejectsNonPositiveQuantity(): void
    {
        $pdo = $this->createMock(\PDO::class);
        $statement = $this->createMock(\PDOStatement::class);
        $statement->method('execute')->willReturn(true);
        $statement->method('fetch')->willReturn([
            'id' => 1,
            'code' => 'MAT-001',
            'name' => 'Item',
            'unit' => 'un',
        ]);
        $pdo->method('prepare')->willReturn($statement);
        $service = new InventoryService(new InventoryRepository($pdo));

        $this->expectException(ApiException::class);
        $service->createMovement(1, 1, [
            'type' => 'entry',
            'quantity' => 0,
        ]);
    }
}
