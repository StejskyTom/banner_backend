<?php
namespace App\User\Application\Action;

readonly class CreateUserAction
{
    public function __construct(
        public string $email,
        public string $password
    ) {}
}
