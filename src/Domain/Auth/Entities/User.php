<?php

namespace Domain\Auth\Entities;

use Domain\Auth\ValueObjects\Email;
use Domain\Auth\ValueObjects\HashedPassword;
use Domain\Auth\ValueObjects\UserId;

class User
{
    public function __construct(
        private UserId $id,
        private string $name,
        private Email $email,
        private HashedPassword $password,
        private \DateTime $createdAt,
        private ?\DateTime $updatedAt = null
    ) {}

    public function getId(): UserId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function getPassword(): HashedPassword
    {
        return $this->password;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function updateName(string $name): void
    {
        $this->name = $name;
        $this->updatedAt = new \DateTime();
    }

    public function updateEmail(Email $email): void
    {
        $this->email = $email;
        $this->updatedAt = new \DateTime();
    }

    public function updatePassword(HashedPassword $password): void
    {
        $this->password = $password;
        $this->updatedAt = new \DateTime();
    }
}
