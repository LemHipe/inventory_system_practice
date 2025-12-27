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

    public function findByIdWithRelations(string $id): ?array
    {
        $dispatch = Dispatch::with(['inventory', 'warehouse', 'dispatcher'])->find($id);

        if (!$dispatch) {
            return null;
        }

        return $this->toArrayWithRelations($dispatch);
    }

    public function findAll(): array
    {
        return Dispatch::with(['inventory', 'warehouse', 'dispatcher'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($dispatch) => $this->toArrayWithRelations($dispatch))
            ->toArray();
    }

    public function findByWarehouse(string $warehouseId): array
    {
        return Dispatch::with(['inventory', 'warehouse', 'dispatcher'])
            ->where('warehouse_id', $warehouseId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($dispatch) => $this->toArrayWithRelations($dispatch))
            ->toArray();
    }

    public function findByDispatcher(int $dispatcherId): array
    {
        return Dispatch::with(['inventory', 'warehouse', 'dispatcher'])
            ->where('dispatcher_id', $dispatcherId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($dispatch) => $this->toArrayWithRelations($dispatch))
            ->toArray();
    }

    public function save(DispatchEntity $dispatch): array
    {
        $model = Dispatch::create([
            'transaction_code' => $dispatch->transactionCode,
            'inventory_id' => $dispatch->inventoryId,
            'warehouse_id' => $dispatch->warehouseId,
            'dispatcher_id' => $dispatch->dispatcherId,
            'quantity' => $dispatch->quantity,
            'destination' => $dispatch->destination,
            'notes' => $dispatch->notes,
            'status' => $dispatch->status,
            'dispatched_at' => $dispatch->dispatchedAt,
        ]);

        return $this->toArrayWithRelations($model->load(['inventory', 'warehouse', 'dispatcher']));
    }

    public function update(string $id, array $data): ?array
    {
        $dispatch = Dispatch::find($id);

        if (!$dispatch) {
            return null;
        }

        $dispatch->update($data);

        return $this->toArrayWithRelations($dispatch->fresh()->load(['inventory', 'warehouse', 'dispatcher']));
    }

    public function delete(string $id): bool
    {
        $dispatch = Dispatch::find($id);

        if (!$dispatch) {
            return false;
        }

        return $dispatch->delete();
    }

    public function generateTransactionCode(): string
    {
        $dateCode = now()->format('Ymd');
        $lastDispatch = Dispatch::where('transaction_code', 'like', "DSP-{$dateCode}-%")
            ->orderBy('transaction_code', 'desc')
            ->first();

        $sequence = 1;
        if ($lastDispatch) {
            $lastSequence = (int) substr($lastDispatch->transaction_code, -4);
            $sequence = $lastSequence + 1;
        }

        return sprintf("DSP-%s-%04d", $dateCode, $sequence);
    }

    private function toEntity(Dispatch $model): DispatchEntity
    {
        return new DispatchEntity(
            id: $model->id,
            transactionCode: $model->transaction_code,
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

    private function toArrayWithRelations(Dispatch $model): array
    {
        return [
            'id' => $model->id,
            'transaction_code' => $model->transaction_code,
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
                'item_code' => $model->inventory->item_code ?? null,
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
