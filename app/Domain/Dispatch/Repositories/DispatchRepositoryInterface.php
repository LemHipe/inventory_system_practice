<?php

namespace App\Domain\Dispatch\Repositories;

use App\Domain\Dispatch\Entities\Dispatch;

interface DispatchRepositoryInterface
{
    public function findById(string $id): ?Dispatch;
    public function findByIdWithRelations(string $id): ?array;
    public function findAll(): array;
    public function findByWarehouse(string $warehouseId): array;
    public function findByDispatcher(int $dispatcherId): array;
    public function save(Dispatch $dispatch): array;
    public function update(string $id, array $data): ?array;
    public function delete(string $id): bool;
    public function generateTransactionCode(): string;
}
