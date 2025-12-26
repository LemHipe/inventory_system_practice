<?php

namespace Infrastructure\Chat\Repositories;

use App\Models\Chat as ChatModel;
use Domain\Auth\ValueObjects\UserId;
use Domain\Chat\Entities\Chat;
use Domain\Chat\Repositories\ChatRepositoryInterface;
use Domain\Chat\ValueObjects\ChatId;

class EloquentChatRepository implements ChatRepositoryInterface
{
    public function findById(ChatId $id): ?Chat
    {
        $model = ChatModel::find($id->getValue());

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findAll(): array
    {
        return ChatModel::query()
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (ChatModel $m) => $this->toDomainEntity($m))
            ->all();
    }

    public function save(Chat $chat): Chat
    {
        $existing = ChatModel::find($chat->getId()->getValue());

        $payload = [
            'id' => $chat->getId()->getValue(),
            'name' => $chat->getName(),
            'created_by' => $chat->getCreatedBy()->getValue(),
        ];

        if (!$existing) {
            $created = ChatModel::create($payload);
            return $this->toDomainEntity($created);
        }

        $existing->update($payload);
        return $this->toDomainEntity($existing->fresh());
    }

    public function delete(ChatId $id): void
    {
        ChatModel::query()->whereKey($id->getValue())->delete();
    }

    private function toDomainEntity(ChatModel $model): Chat
    {
        return new Chat(
            id: new ChatId((string) $model->id),
            name: $model->name,
            createdBy: new UserId((int) $model->created_by),
            createdAt: new \DateTime($model->created_at?->format('c') ?? 'now'),
            updatedAt: $model->updated_at ? new \DateTime($model->updated_at->format('c')) : null
        );
    }
}
