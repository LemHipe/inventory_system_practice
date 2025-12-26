<?php

namespace Application\Auth\Handlers;

use Application\Auth\Commands\RegisterCommand;
use Domain\Auth\Services\AuthService;

class RegisterHandler
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function handle(RegisterCommand $command): array
    {
        try {
            $user = $this->authService->register(
                $command->name,
                $command->email,
                $command->password
            );

            return [
                'success' => true,
                'user' => [
                    'id' => $user->getId()->getValue(),
                    'name' => $user->getName(),
                    'email' => $user->getEmail()->getValue(),
                    'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ];
        } catch (\InvalidArgumentException $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
