<?php

namespace App\Application\Auth\Commands;

class LoginCommand
{
    public function __construct(
        public readonly string $email,
        public readonly string $password
    ) {}
}
