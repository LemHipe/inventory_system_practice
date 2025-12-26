<?php

namespace App\Application\Auth\Handlers;

use App\Application\Auth\Commands\LoginCommand;
use App\Domain\Auth\Services\AuthService;

class LoginHandler
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function handle(LoginCommand $command): array
    {
        return $this->authService->login($command->email, $command->password);
    }
}
