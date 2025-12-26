<?php

namespace App\Domain\Warehouse\Entities;

class Warehouse
{
    public function __construct(
        public readonly ?string $id,
        public readonly string $name,
        public readonly string $address,
        public readonly ?string $city,
        public readonly ?string $state,
        public readonly ?string $postalCode,
        public readonly ?string $phone,
        public readonly bool $isActive = true,
        public readonly ?string $createdAt = null,
        public readonly ?string $updatedAt = null,
    ) {}

    public static function create(
        string $name,
        string $address,
        ?string $city = null,
        ?string $state = null,
        ?string $postalCode = null,
        ?string $phone = null,
    ): self {
        return new self(
            id: null,
            name: $name,
            address: $address,
            city: $city,
            state: $state,
            postalCode: $postalCode,
            phone: $phone,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'phone' => $this->phone,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
