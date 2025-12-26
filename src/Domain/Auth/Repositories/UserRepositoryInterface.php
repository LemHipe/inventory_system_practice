<?php

namespace Domain\Auth\Repositories;

use Domain\Auth\Entities\User;
use Domain\Auth\ValueObjects\Email;
use Domain\Auth\ValueObjects\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;
    public function findByEmail(Email $email): ?User;
    public function save(User $user): User;
    public function delete(UserId $id): void;
    public function existsByEmail(Email $email): bool;
}
