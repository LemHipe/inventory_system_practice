<?php

namespace App\Domain\Dispatch\Entities;

class Dispatch
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $transactionCode,
        public readonly string $inventoryId,
        public readonly string $warehouseId,
        public readonly int $dispatcherId,
        public readonly int $quantity,
        public readonly string $destination,
        public readonly ?string $notes,
        public readonly string $status = 'pending',
        public readonly ?string $dispatchedAt = null,
        public readonly ?string $deliveredAt = null,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {}

    public static function create(
        string $transactionCode,
        string $inventoryId,
        string $warehouseId,
        int $dispatcherId,
        int $quantity,
        string $destination = 'Bosun Hardware',
        ?string $notes = null,
    ): self {
        return new self(
            id: null,
            transactionCode: $transactionCode,
            inventoryId: $inventoryId,
            warehouseId: $warehouseId,
            dispatcherId: $dispatcherId,
            quantity: $quantity,
            destination: $destination,
            notes: $notes,
            dispatchedAt: now()->toDateTimeString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'transaction_code' => $this->transactionCode,
            'inventory_id' => $this->inventoryId,
            'warehouse_id' => $this->warehouseId,
            'dispatcher_id' => $this->dispatcherId,
            'quantity' => $this->quantity,
            'destination' => $this->destination,
            'notes' => $this->notes,
            'status' => $this->status,
            'dispatched_at' => $this->dispatchedAt,
            'delivered_at' => $this->deliveredAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
