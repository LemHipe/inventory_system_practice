<?php

namespace App\Domain\Warehouse\Repositories;

use App\Domain\Warehouse\Entities\Warehouse;

interface WarehouseRepositoryInterface
{
    public function findById(string $id): ?Warehouse;
    public function findAll(): array;
    public function save(Warehouse $warehouse): Warehouse;
    public function update(string $id, array $data): ?Warehouse;
    public function delete(string $id): bool;
}
