<?php

namespace Domain\Inventory\Entities;

use Domain\Inventory\ValueObjects\InventoryId;
use Domain\Inventory\ValueObjects\ProductId;
use Domain\Inventory\ValueObjects\Quantity;
use Domain\Inventory\ValueObjects\Price;

class Inventory
{
    public function __construct(
        private InventoryId $id,
        private ProductId $productId,
        private string $productName,
        private string $description,
        private Quantity $quantity,
        private Price $price,
        private string $category,
        private \DateTime $createdAt,
        private ?\DateTime $updatedAt = null
    ) {}

    public function getId(): InventoryId
    {
        return $this->id;
    }

    public function getProductId(): ProductId
    {
        return $this->productId;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getQuantity(): Quantity
    {
        return $this->quantity;
    }

    public function getPrice(): Price
    {
        return $this->price;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function updateQuantity(Quantity $quantity): void
    {
        $this->quantity = $quantity;
        $this->updatedAt = new \DateTime();
    }

    public function updatePrice(Price $price): void
    {
        $this->price = $price;
        $this->updatedAt = new \DateTime();
    }

    public function updateDetails(string $name, string $description, string $category): void
    {
        $this->productName = $name;
        $this->description = $description;
        $this->category = $category;
        $this->updatedAt = new \DateTime();
    }

    public function addStock(int $amount): void
    {
        $newQuantity = $this->quantity->getValue() + $amount;
        $this->quantity = new Quantity($newQuantity);
        $this->updatedAt = new \DateTime();
    }

    public function removeStock(int $amount): void
    {
        $newQuantity = $this->quantity->getValue() - $amount;
        
        if ($newQuantity < 0) {
            throw new \InvalidArgumentException('Insufficient stock');
        }
        
        $this->quantity = new Quantity($newQuantity);
        $this->updatedAt = new \DateTime();
    }

    public function isLowStock(int $threshold = 10): bool
    {
        return $this->quantity->getValue() <= $threshold;
    }
}
