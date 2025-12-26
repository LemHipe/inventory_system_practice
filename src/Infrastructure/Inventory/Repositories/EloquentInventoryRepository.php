<?php

namespace Infrastructure\Inventory\Repositories;

use App\Models\Inventory as InventoryModel;
use Domain\Inventory\Entities\Inventory;
use Domain\Inventory\Repositories\InventoryRepositoryInterface;
use Domain\Inventory\ValueObjects\InventoryId;
use Domain\Inventory\ValueObjects\Price;
use Domain\Inventory\ValueObjects\ProductId;
use Domain\Inventory\ValueObjects\Quantity;

class EloquentInventoryRepository implements InventoryRepositoryInterface
{
    public function findById(InventoryId $id): ?Inventory
    {
        $model = InventoryModel::find($id->getValue());

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByProductId(ProductId $productId): ?Inventory
    {
        $model = InventoryModel::where('product_id', $productId->getValue())->first();

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findAll(): array
    {
        return InventoryModel::query()
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (InventoryModel $m) => $this->toDomainEntity($m))
            ->all();
    }

    public function findByCategory(string $category): array
    {
        return InventoryModel::query()
            ->where('category', $category)
            ->orderBy('product_name')
            ->get()
            ->map(fn (InventoryModel $m) => $this->toDomainEntity($m))
            ->all();
    }

    public function findLowStock(int $threshold = 10): array
    {
        return InventoryModel::query()
            ->where('quantity', '<=', $threshold)
            ->orderBy('quantity')
            ->get()
            ->map(fn (InventoryModel $m) => $this->toDomainEntity($m))
            ->all();
    }

    public function save(Inventory $inventory): void
    {
        $existing = InventoryModel::find($inventory->getId()->getValue());

        $payload = [
            'id' => $inventory->getId()->getValue(),
            'product_id' => $inventory->getProductId()->getValue(),
            'product_name' => $inventory->getProductName(),
            'description' => $inventory->getDescription(),
            'quantity' => $inventory->getQuantity()->getValue(),
            'price' => $inventory->getPrice()->getValue(),
            'category' => $inventory->getCategory(),
        ];

        if (!$existing) {
            InventoryModel::create($payload);
            return;
        }

        $existing->update($payload);
    }

    public function delete(InventoryId $id): void
    {
        InventoryModel::query()->whereKey($id->getValue())->delete();
    }

    private function toDomainEntity(InventoryModel $model): Inventory
    {
        return new Inventory(
            id: new InventoryId((string) $model->id),
            productId: new ProductId((string) $model->product_id),
            productName: $model->product_name,
            description: $model->description ?? '',
            quantity: new Quantity((int) $model->quantity),
            price: new Price((float) $model->price),
            category: $model->category,
            createdAt: new \DateTime($model->created_at?->format('c') ?? 'now'),
            updatedAt: $model->updated_at ? new \DateTime($model->updated_at->format('c')) : null
        );
    }
}
