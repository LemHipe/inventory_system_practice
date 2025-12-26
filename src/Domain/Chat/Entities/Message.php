<?php

namespace Domain\Chat\Entities;

use Domain\Chat\ValueObjects\ChatId;
use Domain\Chat\ValueObjects\MessageId;
use Domain\Auth\ValueObjects\UserId;

class Message
{
    public function __construct(
        private MessageId $id,
        private ChatId $chatId,
        private UserId $senderId,
        private string $content,
        private \DateTime $createdAt
    ) {}

    public function getId(): MessageId
    {
        return $this->id;
    }

    public function getChatId(): ChatId
    {
        return $this->chatId;
    }

    public function getSenderId(): UserId
    {
        return $this->senderId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }
}
