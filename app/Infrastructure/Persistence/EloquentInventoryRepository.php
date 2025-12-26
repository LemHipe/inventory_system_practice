<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Inventory\Repositories\InventoryRepositoryInterface;
use App\Models\Inventory;

class EloquentInventoryRepository implements InventoryRepositoryInterface
{
    public function findById(string $id): ?array
    {
        $inventory = Inventory::find($id);
        return $inventory ? $inventory->toArray() : null;
    }

    public function findAll(): array
    {
        return Inventory::orderBy('created_at', 'desc')->get()->toArray();
    }

    public function findByCategory(string $category): array
    {
        return Inventory::where('category', $category)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    public function save(array $data): array
    {
        $inventory = Inventory::create($data);
        return $inventory->toArray();
    }

    public function update(string $id, array $data): ?array
    {
        $inventory = Inventory::find($id);
        
        if (!$inventory) {
            return null;
        }

        $inventory->update($data);
        return $inventory->fresh()->toArray();
    }

    public function delete(string $id): bool
    {
        $inventory = Inventory::find($id);
        
        if (!$inventory) {
            return false;
        }

        return $inventory->delete();
    }
}
