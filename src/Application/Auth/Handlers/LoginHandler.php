<?php

namespace Application\Auth\Handlers;

use Application\Auth\Commands\LoginCommand;
use Domain\Auth\Services\AuthService;

class LoginHandler
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function handle(LoginCommand $command): array
    {
        $user = $this->authService->login(
            $command->email,
            $command->password
        );

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid credentials'
            ];
        }

        // Generate token (simplified - in production use JWT or Sanctum)
        $token = md5($user->getId()->getValue() . time());

        return [
            'success' => true,
            'user' => [
                'id' => $user->getId()->getValue(),
                'name' => $user->getName(),
                'email' => $user->getEmail()->getValue(),
                'created_at' => $user->getCreatedAt()->format('Y-m-d H:i:s')
            ],
            'token' => $token
        ];
    }
}
