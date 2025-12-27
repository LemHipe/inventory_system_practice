<?php

namespace App\Application\Services;

use App\Domain\Dispatch\Entities\Dispatch;
use App\Domain\Dispatch\Repositories\DispatchRepositoryInterface;
use App\Domain\Inventory\Repositories\InventoryRepositoryInterface;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;

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

    public function getDispatchById(string $id): ?array
    {
        return $this->dispatchRepository->findByIdWithRelations($id);
    }

    public function getDispatchesByWarehouse(string $warehouseId): array
    {
        return $this->dispatchRepository->findByWarehouse($warehouseId);
    }

    public function getDispatchesByDispatcher(int $dispatcherId): array
    {
        return $this->dispatchRepository->findByDispatcher($dispatcherId);
    }

    public function validateStock(string $inventoryId, int $quantity): array
    {
        $inventory = $this->inventoryRepository->findById($inventoryId);

        if (!$inventory) {
            return ['valid' => false, 'error' => 'Inventory item not found', 'code' => 404];
        }

        if ($inventory['quantity'] < $quantity) {
            return ['valid' => false, 'error' => 'Insufficient stock. Available: ' . $inventory['quantity'], 'code' => 422];
        }

        return ['valid' => true, 'inventory' => $inventory];
    }

    public function createDispatch(array $data, int $dispatcherId, ?string $ipAddress = null): array
    {
        $inventory = $this->inventoryRepository->findById($data['inventory_id']);
        $oldQuantity = $inventory['quantity'];

        $transactionCode = $this->dispatchRepository->generateTransactionCode();

        $result = DB::transaction(function () use ($data, $dispatcherId, $transactionCode) {
            // Deduct inventory stock
            $inventory = $this->inventoryRepository->findById($data['inventory_id']);
            $newQuantity = max(0, $inventory['quantity'] - $data['quantity']);
            $this->inventoryRepository->update($data['inventory_id'], ['quantity' => $newQuantity]);

            // Create dispatch entity
            $dispatch = Dispatch::create(
                transactionCode: $transactionCode,
                inventoryId: $data['inventory_id'],
                warehouseId: $data['warehouse_id'],
                dispatcherId: $dispatcherId,
                quantity: $data['quantity'],
                destination: $data['destination'] ?? 'Bosun Hardware',
                notes: $data['notes'] ?? null,
            );

            return $this->dispatchRepository->save($dispatch);
        });

        // Log the dispatch activity
        $newInventory = $this->inventoryRepository->findById($data['inventory_id']);
        ActivityLog::log(
            userId: $dispatcherId,
            action: 'dispatched',
            modelType: 'Dispatch',
            modelId: $result['id'],
            description: "Dispatched {$data['quantity']} units of {$inventory['product_name']} to {$result['destination']} (Stock: {$oldQuantity} → {$newInventory['quantity']})",
            newValues: $result,
            ipAddress: $ipAddress
        );

        return $result;
    }

    public function updateDispatch(string $id, array $data, int $userId, ?string $ipAddress = null): ?array
    {
        $existingDispatch = $this->dispatchRepository->findByIdWithRelations($id);

        if (!$existingDispatch) {
            return null;
        }

        $oldStatus = $existingDispatch['status'];

        if (isset($data['status']) && $data['status'] === 'delivered') {
            $data['delivered_at'] = now()->toDateTimeString();
        }

        $result = $this->dispatchRepository->update($id, $data);

        // Log status changes
        if (isset($data['status']) && $oldStatus !== $data['status']) {
            $productName = $existingDispatch['inventory']['product_name'] ?? 'Unknown';
            ActivityLog::log(
                userId: $userId,
                action: 'status_changed',
                modelType: 'Dispatch',
                modelId: $id,
                description: "Dispatch status changed: {$oldStatus} → {$data['status']} ({$productName} to {$existingDispatch['destination']})",
                oldValues: ['status' => $oldStatus],
                newValues: ['status' => $data['status']],
                ipAddress: $ipAddress
            );
        }

        return $result;
    }

    public function deleteDispatch(string $id): bool
    {
        return $this->dispatchRepository->delete($id);
    }
}
