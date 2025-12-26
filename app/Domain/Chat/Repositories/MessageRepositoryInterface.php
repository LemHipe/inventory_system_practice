<?php

namespace App\Domain\Chat\Repositories;

interface MessageRepositoryInterface
{
    public function findByChatId(string $chatId): array;
    public function create(array $data): array;
}
