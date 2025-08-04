<?php
namespace App\User\Application\Action;

use App\Entity\User;

readonly class UpdatePassword
{
    public function __construct(
        public User $user,
        #[\SensitiveParameter] public string $password
    ) {}
}
