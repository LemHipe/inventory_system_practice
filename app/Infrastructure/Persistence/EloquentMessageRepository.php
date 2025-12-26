<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Chat\Repositories\MessageRepositoryInterface;
use App\Models\ChatMessage;

class EloquentMessageRepository implements MessageRepositoryInterface
{
    public function findByChatId(string $chatId): array
    {
        return ChatMessage::where('chat_id', $chatId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();
    }

    public function create(array $data): array
    {
        $message = ChatMessage::create($data);
        return $message->toArray();
    }
}
