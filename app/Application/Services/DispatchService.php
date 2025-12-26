<?php

namespace App\Application\Services;

use App\Domain\Dispatch\Entities\Dispatch;
use App\Domain\Dispatch\Repositories\DispatchRepositoryInterface;
use App\Domain\Inventory\Repositories\InventoryRepositoryInterface;

class DispatchService
{
    public function __construct(
        private DispatchRepositoryInterface $dispatchRepository,
        private InventoryRepositoryInterface $inventoryRepository
    ) {}

    public function getAllDispatches(): array
    {
        return $this->dispatchRepository->findAll();
    }

    public function getDispatchById(string $id): ?Dispatch
    {
        return $this->dispatchRepository->findById($id);
    }

    public function getDispatchesByWarehouse(string $warehouseId): array
    {
        return $this->dispatchRepository->findByWarehouse($warehouseId);
    }

    public function getDispatchesByDispatcher(int $dispatcherId): array
    {
        return $this->dispatchRepository->findByDispatcher($dispatcherId);
    }

    public function createDispatch(array $data, int $dispatcherId): Dispatch
    {
        $dispatch = Dispatch::create(
            inventoryId: $data['inventory_id'],
            warehouseId: $data['warehouse_id'],
            dispatcherId: $dispatcherId,
            quantity: $data['quantity'],
            destination: $data['destination'] ?? 'Bosun Hardware',
            notes: $data['notes'] ?? null,
        );

        // Reduce inventory quantity
        $inventory = $this->inventoryRepository->findById($data['inventory_id']);
        if ($inventory) {
            $newQuantity = max(0, $inventory->quantity - $data['quantity']);
            $this->inventoryRepository->update($data['inventory_id'], ['quantity' => $newQuantity]);
        }

        return $this->dispatchRepository->save($dispatch);
    }

    public function updateDispatch(string $id, array $data): ?Dispatch
    {
        if (isset($data['status']) && $data['status'] === 'delivered') {
            $data['delivered_at'] = now()->toDateTimeString();
        }

        return $this->dispatchRepository->update($id, $data);
    }

    public function deleteDispatch(string $id): bool
    {
        return $this->dispatchRepository->delete($id);
    }
}
