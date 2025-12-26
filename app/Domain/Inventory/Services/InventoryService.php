<?php

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Repositories\InventoryRepositoryInterface;

class InventoryService
{
    public function __construct(
        private InventoryRepositoryInterface $repository
    ) {}

    public function getAllInventory(): array
    {
        return $this->repository->findAll();
    }

    public function getInventoryById(string $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function getInventoryByCategory(string $category): array
    {
        return $this->repository->findByCategory($category);
    }

    public function createInventory(array $data): array
    {
        return $this->repository->save($data);
    }

    public function updateInventory(string $id, array $data): ?array
    {
        return $this->repository->update($id, $data);
    }

    public function deleteInventory(string $id): bool
    {
        return $this->repository->delete($id);
    }
}
