<?php

namespace App\Domain\Chat\Services;

use App\Domain\Chat\Repositories\ChatRepositoryInterface;
use App\Domain\Chat\Repositories\MessageRepositoryInterface;

class ChatService
{
    public function __construct(
        private ChatRepositoryInterface $chatRepository,
        private MessageRepositoryInterface $messageRepository
    ) {}

    public function getAllChats(): array
    {
        return $this->chatRepository->findAll();
    }

    public function getChatById(string $id): ?array
    {
        return $this->chatRepository->findById($id);
    }

    public function createChat(array $data): array
    {
        return $this->chatRepository->create($data);
    }

    public function getMessages(string $chatId): array
    {
        return $this->messageRepository->findByChatId($chatId);
    }

    public function sendMessage(array $data): array
    {
        return $this->messageRepository->create($data);
    }
}
