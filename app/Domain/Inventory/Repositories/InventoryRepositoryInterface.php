<?php

namespace App\Domain\Inventory\Repositories;

interface InventoryRepositoryInterface
{
    public function findById(string $id): ?array;
    public function findAll(): array;
    public function findByCategory(string $category): array;
    public function save(array $data): array;
    public function update(string $id, array $data): ?array;
    public function delete(string $id): bool;
}
