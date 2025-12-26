<?php

namespace Domain\Chat\Services;

use Domain\Auth\ValueObjects\UserId;
use Domain\Chat\Entities\Chat;
use Domain\Chat\Entities\Message;
use Domain\Chat\Repositories\ChatRepositoryInterface;
use Domain\Chat\Repositories\MessageRepositoryInterface;
use Domain\Chat\ValueObjects\ChatId;
use Domain\Chat\ValueObjects\MessageId;

class ChatService
{
    public function __construct(
        private ChatRepositoryInterface $chatRepository,
        private MessageRepositoryInterface $messageRepository
    ) {}

    public function createChat(string $name, UserId $createdBy): Chat
    {
        $chat = new Chat(
            id: ChatId::generate(),
            name: $name,
            createdBy: $createdBy,
            createdAt: new \DateTime()
        );

        return $this->chatRepository->save($chat);
    }

    public function listChats(): array
    {
        return $this->chatRepository->findAll();
    }

    public function getMessages(ChatId $chatId, int $limit = 50): array
    {
        return $this->messageRepository->findByChatId($chatId, $limit);
    }

    public function sendMessage(ChatId $chatId, UserId $senderId, string $content): Message
    {
        $message = new Message(
            id: MessageId::generate(),
            chatId: $chatId,
            senderId: $senderId,
            content: $content,
            createdAt: new \DateTime()
        );

        return $this->messageRepository->save($message);
    }
}
