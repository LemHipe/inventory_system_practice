<?php

namespace Domain\Inventory\Services;

use Domain\Inventory\Entities\Inventory;
use Domain\Inventory\Repositories\InventoryRepositoryInterface;
use Domain\Inventory\ValueObjects\InventoryId;
use Domain\Inventory\ValueObjects\Price;
use Domain\Inventory\ValueObjects\ProductId;
use Domain\Inventory\ValueObjects\Quantity;

class InventoryService
{
    public function __construct(
        private InventoryRepositoryInterface $inventoryRepository
    ) {}

    public function addInventory(
        string $productName,
        string $description,
        int $quantity,
        float $price,
        string $category
    ): Inventory {
        $inventory = new Inventory(
            id: InventoryId::generate(),
            productId: ProductId::generate(),
            productName: $productName,
            description: $description,
            quantity: new Quantity($quantity),
            price: new Price($price),
            category: $category,
            createdAt: new \DateTime()
        );

        $this->inventoryRepository->save($inventory);
        return $inventory;
    }

    public function updateStock(InventoryId $id, int $quantity): void
    {
        $inventory = $this->inventoryRepository->findById($id);
        
        if (!$inventory) {
            throw new \InvalidArgumentException('Inventory item not found');
        }

        $inventory->updateQuantity(new Quantity($quantity));
        $this->inventoryRepository->save($inventory);
    }

    public function addStock(InventoryId $id, int $amount): void
    {
        $inventory = $this->inventoryRepository->findById($id);
        
        if (!$inventory) {
            throw new \InvalidArgumentException('Inventory item not found');
        }

        $inventory->addStock($amount);
        $this->inventoryRepository->save($inventory);
    }

    public function removeStock(InventoryId $id, int $amount): void
    {
        $inventory = $this->inventoryRepository->findById($id);
        
        if (!$inventory) {
            throw new \InvalidArgumentException('Inventory item not found');
        }

        $inventory->removeStock($amount);
        $this->inventoryRepository->save($inventory);
    }

    public function getInventoryById(InventoryId $id): ?Inventory
    {
        return $this->inventoryRepository->findById($id);
    }

    public function getAllInventory(): array
    {
        return $this->inventoryRepository->findAll();
    }

    public function getLowStockItems(int $threshold = 10): array
    {
        return $this->inventoryRepository->findLowStock($threshold);
    }

    public function getInventoryByCategory(string $category): array
    {
        return $this->inventoryRepository->findByCategory($category);
    }

    public function deleteInventory(InventoryId $id): void
    {
        $this->inventoryRepository->delete($id);
    }
}
