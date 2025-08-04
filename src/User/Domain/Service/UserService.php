<?php
namespace App\User\Domain\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\User\Domain\Exception\InvalidEmailException;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Exception\WeakPasswordException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    /**
     * @throws UserAlreadyExistsException
     */
    public function registerUser(string $email, string $plainPassword): User
    {
        $this->ensureUserDoesNotExist($email);

        $user = new User();
        $user->setEmail($email);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        return $user;
    }

    /**
     * @throws UserAlreadyExistsException
     */
    private function ensureUserDoesNotExist(string $email): void
    {
        $existingUser = $this->userRepository->findOneBy(['email' => $email]);

        if ($existingUser) {
            throw new UserAlreadyExistsException('Uživatel s tímto emailem už existuje');
        }
    }
}
