<?php

namespace App\Domain\Dispatch\Repositories;

use App\Domain\Dispatch\Entities\Dispatch;

interface DispatchRepositoryInterface
{
    public function findById(string $id): ?Dispatch;
    public function findAll(): array;
    public function findByWarehouse(string $warehouseId): array;
    public function findByDispatcher(int $dispatcherId): array;
    public function save(Dispatch $dispatch): Dispatch;
    public function update(string $id, array $data): ?Dispatch;
    public function delete(string $id): bool;
}
