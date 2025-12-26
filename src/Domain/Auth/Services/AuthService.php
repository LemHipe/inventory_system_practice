<?php

namespace Domain\Auth\Services;

use Domain\Auth\Entities\User;
use Domain\Auth\Repositories\UserRepositoryInterface;
use Domain\Auth\ValueObjects\Email;
use Domain\Auth\ValueObjects\HashedPassword;
use Domain\Auth\ValueObjects\UserId;

class AuthService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function register(string $name, string $email, string $password): User
    {
        $emailVO = new Email($email);
        
        if ($this->userRepository->existsByEmail($emailVO)) {
            throw new \InvalidArgumentException('Email already exists');
        }

        $hashedPassword = HashedPassword::fromPlainPassword($password);
        $user = new User(
            id: new UserId(0),
            name: $name,
            email: $emailVO,
            password: $hashedPassword,
            createdAt: new \DateTime()
        );

        return $this->userRepository->save($user);
    }

    public function login(string $email, string $password): ?User
    {
        $emailVO = new Email($email);
        $user = $this->userRepository->findByEmail($emailVO);

        if (!$user || !$user->getPassword()->verify($password)) {
            return null;
        }

        return $user;
    }

    public function getUserById(UserId $id): ?User
    {
        return $this->userRepository->findById($id);
    }
}
