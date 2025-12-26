<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Dispatch\Entities\Dispatch as DispatchEntity;
use App\Domain\Dispatch\Repositories\DispatchRepositoryInterface;
use App\Models\Dispatch;

class EloquentDispatchRepository implements DispatchRepositoryInterface
{
    public function findById(string $id): ?DispatchEntity
    {
        $dispatch = Dispatch::find($id);

        if (!$dispatch) {
            return null;
        }

        return $this->toEntity($dispatch);
    }

    public function findAll(): array
    {
        return Dispatch::with(['inventory', 'warehouse', 'dispatcher'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($dispatch) => $this->toEntityWithRelations($dispatch))
            ->toArray();
    }

    public function findByWarehouse(string $warehouseId): array
    {
        return Dispatch::with(['inventory', 'warehouse', 'dispatcher'])
            ->where('warehouse_id', $warehouseId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($dispatch) => $this->toEntityWithRelations($dispatch))
            ->toArray();
    }

    public function findByDispatcher(int $dispatcherId): array
    {
        return Dispatch::with(['inventory', 'warehouse', 'dispatcher'])
            ->where('dispatcher_id', $dispatcherId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($dispatch) => $this->toEntityWithRelations($dispatch))
            ->toArray();
    }

    public function save(DispatchEntity $dispatch): DispatchEntity
    {
        $model = Dispatch::create([
            'inventory_id' => $dispatch->inventoryId,
            'warehouse_id' => $dispatch->warehouseId,
            'dispatcher_id' => $dispatch->dispatcherId,
            'quantity' => $dispatch->quantity,
            'destination' => $dispatch->destination,
            'notes' => $dispatch->notes,
            'status' => $dispatch->status,
            'dispatched_at' => $dispatch->dispatchedAt,
        ]);

        return $this->toEntity($model);
    }

    public function update(string $id, array $data): ?DispatchEntity
    {
        $dispatch = Dispatch::find($id);

        if (!$dispatch) {
            return null;
        }

        $dispatch->update($data);

        return $this->toEntity($dispatch->fresh());
    }

    public function delete(string $id): bool
    {
        $dispatch = Dispatch::find($id);

        if (!$dispatch) {
            return false;
        }

        return $dispatch->delete();
    }

    private function toEntity(Dispatch $model): DispatchEntity
    {
        return new DispatchEntity(
            id: $model->id,
            inventoryId: $model->inventory_id,
            warehouseId: $model->warehouse_id,
            dispatcherId: $model->dispatcher_id,
            quantity: $model->quantity,
            destination: $model->destination,
            notes: $model->notes,
            status: $model->status,
            dispatchedAt: $model->dispatched_at?->toDateTimeString(),
            deliveredAt: $model->delivered_at?->toDateTimeString(),
            createdAt: $model->created_at?->toDateTimeString(),
            updatedAt: $model->updated_at?->toDateTimeString(),
        );
    }

    private function toEntityWithRelations(Dispatch $model): array
    {
        return [
            'id' => $model->id,
            'inventory_id' => $model->inventory_id,
            'warehouse_id' => $model->warehouse_id,
            'dispatcher_id' => $model->dispatcher_id,
            'quantity' => $model->quantity,
            'destination' => $model->destination,
            'notes' => $model->notes,
            'status' => $model->status,
            'dispatched_at' => $model->dispatched_at?->toDateTimeString(),
            'delivered_at' => $model->delivered_at?->toDateTimeString(),
            'created_at' => $model->created_at?->toDateTimeString(),
            'updated_at' => $model->updated_at?->toDateTimeString(),
            'inventory' => $model->inventory ? [
                'id' => $model->inventory->id,
                'product_name' => $model->inventory->product_name,
            ] : null,
            'warehouse' => $model->warehouse ? [
                'id' => $model->warehouse->id,
                'name' => $model->warehouse->name,
            ] : null,
            'dispatcher' => $model->dispatcher ? [
                'id' => $model->dispatcher->id,
                'name' => $model->dispatcher->name,
            ] : null,
        ];
    }
}
