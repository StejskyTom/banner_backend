<?php
namespace App\User\Application\Handler;

use App\Entity\User;
use App\Lib\ViolationsTrait;
use App\User\Application\Action\CreateUserAction;
use App\User\Domain\Exception\InvalidEmailException;
use App\User\Domain\Exception\UserAlreadyExistsException;
use App\User\Domain\Exception\UserDomainException;
use App\User\Domain\Exception\WeakPasswordException;
use App\User\Domain\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

readonly class CreateUserHandler
{
    use ViolationsTrait;

    public function __construct(
        private UserService            $userService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface        $logger
    ) {}

    /**
     * @throws UserAlreadyExistsException
     * @throws UserDomainException
     * @throws WeakPasswordException
     * @throws InvalidEmailException
     */
    #[AsMessageHandler]
    public function onCreateUser(CreateUserAction $action): User
    {
        try {
            $user = $this->userService->registerUser(
                $action->email,
                $action->password
            );

            $this->logger->info('Uživatel byl úspěšně zaregistrován', [
                'email' => $action->email
            ]);

        } catch (UserAlreadyExistsException $e) {
            $this->createFieldValidationFailedException(
                'Uživatel s tímto emailem již existuje.',
                'email'
            );
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
