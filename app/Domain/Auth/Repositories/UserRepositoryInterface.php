<?php

namespace App\Domain\Auth\Repositories;

interface UserRepositoryInterface
{
    public function findById(int $id): ?array;
    public function findByEmail(string $email): ?array;
    public function create(array $data): array;
}
