<?php

namespace Application\Auth\Commands;

class RegisterCommand
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password
    ) {}
}
