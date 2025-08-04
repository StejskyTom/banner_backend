<?php
namespace App\User\Application\Handler;

use App\User\Application\Action\UpdatePassword;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class UpdatePasswordHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $hasher,
    ) {}

    #[AsMessageHandler]
    public function onPasswordUpdate(UpdatePassword $action): void
    {
        $hashedPassword = $this->hasher->hashPassword($action->user, $action->password);
        $action->user->setPassword($hashedPassword);
        $this->entityManager->flush();
    }
}
