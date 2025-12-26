<?php

namespace Domain\Chat\ValueObjects;

use Symfony\Component\Uid\Uuid;

final readonly class ChatId
{
    private string $value;

    public function __construct(string $value = null)
    {
        $this->value = $value ?? Uuid::v4()->toRfc4122();
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function generate(): self
    {
        return new self();
    }
}
