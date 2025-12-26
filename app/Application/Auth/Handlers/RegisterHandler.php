<?php

namespace App\Application\Auth\Handlers;

use App\Application\Auth\Commands\RegisterCommand;
use App\Domain\Auth\Services\AuthService;

class RegisterHandler
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function handle(RegisterCommand $command): array
    {
        return $this->authService->register($command->name, $command->email, $command->password);
    }
}
