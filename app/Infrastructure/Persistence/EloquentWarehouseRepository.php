<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Warehouse\Entities\Warehouse as WarehouseEntity;
use App\Domain\Warehouse\Repositories\WarehouseRepositoryInterface;
use App\Models\Warehouse;

class EloquentWarehouseRepository implements WarehouseRepositoryInterface
{
    public function findById(string $id): ?WarehouseEntity
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return null;
        }

        return $this->toEntity($warehouse);
    }

    public function findAll(): array
    {
        return Warehouse::orderBy('name')
            ->get()
            ->map(fn($warehouse) => $this->toEntity($warehouse))
            ->toArray();
    }

    public function save(WarehouseEntity $warehouse): WarehouseEntity
    {
        $model = Warehouse::create([
            'name' => $warehouse->name,
            'address' => $warehouse->address,
            'city' => $warehouse->city,
            'state' => $warehouse->state,
            'postal_code' => $warehouse->postalCode,
            'phone' => $warehouse->phone,
            'is_active' => $warehouse->isActive,
        ]);

        return $this->toEntity($model);
    }

    public function update(string $id, array $data): ?WarehouseEntity
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return null;
        }

        $warehouse->update($data);

        return $this->toEntity($warehouse->fresh());
    }

    public function delete(string $id): bool
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return false;
        }

        return $warehouse->delete();
    }

    private function toEntity(Warehouse $model): WarehouseEntity
    {
        return new WarehouseEntity(
            id: $model->id,
            name: $model->name,
            address: $model->address,
            city: $model->city,
            state: $model->state,
            postalCode: $model->postal_code,
            phone: $model->phone,
            isActive: $model->is_active,
            createdAt: $model->created_at?->toDateTimeString(),
            updatedAt: $model->updated_at?->toDateTimeString(),
        );
    }
}
