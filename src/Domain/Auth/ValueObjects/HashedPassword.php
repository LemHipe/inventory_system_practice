<?php

namespace Domain\Auth\ValueObjects;

use InvalidArgumentException;

final readonly class HashedPassword
{
    private string $value;

    public function __construct(string $value)
    {
        if (strlen($value) < 60) { // bcrypt hash length
            throw new InvalidArgumentException('Invalid password hash format');
        }

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function verify(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->value);
    }

    public static function fromPlainPassword(string $plainPassword): self
    {
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        
        if ($hash === false) {
            throw new \RuntimeException('Failed to hash password');
        }

        return new self($hash);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
