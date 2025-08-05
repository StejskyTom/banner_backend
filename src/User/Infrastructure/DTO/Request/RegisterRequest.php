<?php
namespace App\User\Infrastructure\DTO\Request;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterRequest
{
    #[NotBlank()]
    #[Email()]
    public string $email;

    #[NotBlank()]
    public string $password;
}
