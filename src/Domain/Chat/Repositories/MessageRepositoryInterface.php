<?php

namespace Domain\Chat\Repositories;

use Domain\Chat\Entities\Message;
use Domain\Chat\ValueObjects\ChatId;
use Domain\Chat\ValueObjects\MessageId;

interface MessageRepositoryInterface
{
    public function findById(MessageId $id): ?Message;
    public function findByChatId(ChatId $chatId, int $limit = 50): array;
    public function save(Message $message): Message;
}
