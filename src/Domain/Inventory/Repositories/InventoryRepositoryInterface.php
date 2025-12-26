<?php

namespace Domain\Inventory\Repositories;

use Domain\Inventory\Entities\Inventory;
use Domain\Inventory\ValueObjects\InventoryId;
use Domain\Inventory\ValueObjects\ProductId;

interface InventoryRepositoryInterface
{
    public function findById(InventoryId $id): ?Inventory;
    public function findByProductId(ProductId $productId): ?Inventory;
    public function findAll(): array;
    public function findByCategory(string $category): array;
    public function findLowStock(int $threshold = 10): array;
    public function save(Inventory $inventory): void;
    public function delete(InventoryId $id): void;
}
