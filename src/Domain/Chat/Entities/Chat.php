<?php

namespace Domain\Chat\Entities;

use Domain\Chat\ValueObjects\ChatId;
use Domain\Auth\ValueObjects\UserId;
use Domain\Chat\ValueObjects\MessageId;

class Chat
{
    private array $messages = [];

    public function __construct(
        private ChatId $id,
        private string $name,
        private UserId $createdBy,
        private \DateTime $createdAt,
        private ?\DateTime $updatedAt = null
    ) {}

    public function getId(): ChatId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCreatedBy(): UserId
    {
        return $this->createdBy;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function addMessage(Message $message): void
    {
        $this->messages[] = $message;
        $this->updatedAt = new \DateTime();
    }

    public function getLastMessage(): ?Message
    {
        if (empty($this->messages)) {
            return null;
        }

        return end($this->messages);
    }
}
