<?php

namespace App\Domain\Chat\Repositories;

interface ChatRepositoryInterface
{
    public function findById(string $id): ?array;
    public function findAll(): array;
    public function create(array $data): array;
    public function delete(string $id): bool;
}
