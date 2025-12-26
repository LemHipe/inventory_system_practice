<?php

namespace Domain\Chat\Repositories;

use Domain\Chat\Entities\Chat;
use Domain\Chat\ValueObjects\ChatId;

interface ChatRepositoryInterface
{
    public function findById(ChatId $id): ?Chat;
    public function findAll(): array;
    public function save(Chat $chat): Chat;
    public function delete(ChatId $id): void;
}
