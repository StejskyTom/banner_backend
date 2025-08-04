<?php
namespace App\User\Infrastructure\DTO\Request;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest
{
    #[Assert\NotBlank(message: 'Email je povinný')]
    #[Assert\Email(message: 'Neplatný formát emailu')]
    #[Assert\Length(max: 180, maxMessage: 'Email je příliš dlouhý')]
    public string $email;

    #[Assert\NotBlank(message: 'Heslo je povinné')]
    #[Assert\Length(min: 8, minMessage: 'Heslo musí mít alespoň 8 znaků')]
    public string $password;

    public static function fromArray(array $data): self
    {
        $request = new self();
        $request->email = $data['email'] ?? '';
        $request->password = $data['password'] ?? '';

        return $request;
    }
}
