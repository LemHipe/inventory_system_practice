<?php

namespace App\Infrastructure\Persistence;

use App\Domain\Auth\Repositories\UserRepositoryInterface;
use App\Models\User;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?array
    {
        $user = User::find($id);
        return $user ? $user->toArray() : null;
    }

    public function findByEmail(string $email): ?array
    {
        $user = User::where('email', $email)->first();
        return $user ? $user->toArray() : null;
    }

    public function create(array $data): array
    {
        $user = User::create($data);
        return $user->toArray();
    }
}
