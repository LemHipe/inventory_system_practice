<?php

namespace Domain\Inventory\ValueObjects;

use InvalidArgumentException;

final readonly class Price
{
    private float $value;

    public function __construct(float $value)
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Price cannot be negative');
        }

        $this->value = round($value, 2);
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getFormattedValue(): string
    {
        return '$' . number_format($this->value, 2);
    }

    public function __toString(): string
    {
        return $this->getFormattedValue();
    }
}
