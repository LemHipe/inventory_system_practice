<?php

namespace Infrastructure\Auth\Repositories;

use Domain\Auth\Entities\User;
use Domain\Auth\Repositories\UserRepositoryInterface;
use Domain\Auth\ValueObjects\Email;
use Domain\Auth\ValueObjects\HashedPassword;
use Domain\Auth\ValueObjects\UserId;
use App\Models\User as UserModel;

class EloquentUserRepository implements UserRepositoryInterface
{
    public function findById(UserId $id): ?User
    {
        $userModel = UserModel::find($id->getValue());
        
        if (!$userModel) {
            return null;
        }

        return $this->toDomainEntity($userModel);
    }

    public function findByEmail(Email $email): ?User
    {
        $userModel = UserModel::where('email', $email->getValue())->first();
        
        if (!$userModel) {
            return null;
        }

        return $this->toDomainEntity($userModel);
    }

    public function save(User $user): User
    {
        $existing = $user->getId()->getValue() > 0
            ? UserModel::find($user->getId()->getValue())
            : null;

        if (!$existing) {
            $created = UserModel::create([
                'name' => $user->getName(),
                'email' => $user->getEmail()->getValue(),
                'password' => $user->getPassword()->getValue(),
            ]);

            return $this->toDomainEntity($created);
        }

        $existing->update([
            'name' => $user->getName(),
            'email' => $user->getEmail()->getValue(),
            'password' => $user->getPassword()->getValue(),
        ]);

        return $this->toDomainEntity($existing->fresh());
    }

    public function delete(UserId $id): void
    {
        UserModel::destroy($id->getValue());
    }

    public function existsByEmail(Email $email): bool
    {
        return UserModel::where('email', $email->getValue())->exists();
    }

    private function toDomainEntity(UserModel $userModel): User
    {
        return new User(
            id: new UserId((int) $userModel->id),
            name: $userModel->name,
            email: new Email($userModel->email),
            password: new HashedPassword($userModel->password),
            createdAt: new \DateTime($userModel->created_at?->format('c') ?? 'now'),
            updatedAt: $userModel->updated_at ? new \DateTime($userModel->updated_at->format('c')) : null
        );
    }
}
