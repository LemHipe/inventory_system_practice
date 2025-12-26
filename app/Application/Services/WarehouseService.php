<?php

namespace App\Application\Services;

use App\Domain\Warehouse\Entities\Warehouse;
use App\Domain\Warehouse\Repositories\WarehouseRepositoryInterface;

class WarehouseService
{
    public function __construct(
        private WarehouseRepositoryInterface $warehouseRepository
    ) {}

    public function getAllWarehouses(): array
    {
        return $this->warehouseRepository->findAll();
    }

    public function getWarehouseById(string $id): ?Warehouse
    {
        return $this->warehouseRepository->findById($id);
    }

    public function createWarehouse(array $data): Warehouse
    {
        $warehouse = Warehouse::create(
            name: $data['name'],
            address: $data['address'],
            city: $data['city'] ?? null,
            state: $data['state'] ?? null,
            postalCode: $data['postal_code'] ?? null,
            phone: $data['phone'] ?? null,
        );

        return $this->warehouseRepository->save($warehouse);
    }

    public function updateWarehouse(string $id, array $data): ?Warehouse
    {
        return $this->warehouseRepository->update($id, $data);
    }

    public function deleteWarehouse(string $id): bool
    {
        return $this->warehouseRepository->delete($id);
    }
}
