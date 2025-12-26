<?php

namespace Infrastructure\Chat\Repositories;

use App\Models\ChatMessage as ChatMessageModel;
use Domain\Auth\ValueObjects\UserId;
use Domain\Chat\Entities\Message;
use Domain\Chat\Repositories\MessageRepositoryInterface;
use Domain\Chat\ValueObjects\ChatId;
use Domain\Chat\ValueObjects\MessageId;

class EloquentMessageRepository implements MessageRepositoryInterface
{
    public function findById(MessageId $id): ?Message
    {
        $model = ChatMessageModel::find($id->getValue());

        return $model ? $this->toDomainEntity($model) : null;
    }

    public function findByChatId(ChatId $chatId, int $limit = 50): array
    {
        return ChatMessageModel::query()
            ->where('chat_id', $chatId->getValue())
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (ChatMessageModel $m) => $this->toDomainEntity($m))
            ->all();
    }

    public function save(Message $message): Message
    {
        $payload = [
            'id' => $message->getId()->getValue(),
            'chat_id' => $message->getChatId()->getValue(),
            'sender_id' => $message->getSenderId()->getValue(),
            'content' => $message->getContent(),
        ];

        $existing = ChatMessageModel::find($message->getId()->getValue());

        if (!$existing) {
            $created = ChatMessageModel::create($payload);
            return $this->toDomainEntity($created);
        }

        $existing->update($payload);
        return $this->toDomainEntity($existing->fresh());
    }

    private function toDomainEntity(ChatMessageModel $model): Message
    {
        return new Message(
            id: new MessageId((string) $model->id),
            chatId: new ChatId((string) $model->chat_id),
            senderId: new UserId((int) $model->sender_id),
            content: $model->content,
            createdAt: new \DateTime($model->created_at?->format('c') ?? 'now')
        );
    }
}
