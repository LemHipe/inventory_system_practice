<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Chat\Repositories\ChatRepositoryInterface;
use App\Models\Chat;

class EloquentChatRepository implements ChatRepositoryInterface
{
    public function findById(string $id): ?array
    {
        $chat = Chat::find($id);
        return $chat ? $chat->toArray() : null;
    }

    public function findAll(): array
    {
        return Chat::orderBy('created_at', 'desc')->get()->toArray();
    }

    public function create(array $data): array
    {
        $chat = Chat::create($data);
        return $chat->toArray();
    }

    public function delete(string $id): bool
    {
        $chat = Chat::find($id);
        
        if (!$chat) {
            return false;
        }

        return $chat->delete();
    }
}
