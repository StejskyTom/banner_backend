<?php
// src/Controller/Api/RegistrationController.php
namespace App\User\Application\Controller;

use App\User\Application\Action\CreateUserAction;
use App\User\Application\Action\UpdatePassword;
use App\User\Infrastructure\DTO\Request\RegisterRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class RegistrationController extends AbstractController
{
    use HandleTrait;

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        EntityManagerInterface $entityManager,
        #[MapRequestPayload] RegisterRequest $registerRequest,
        MessageBusInterface $messageBus,
    ): JsonResponse
    {
        $this->messageBus = $messageBus;

        $createUser = new CreateUserAction($registerRequest->email, $registerRequest->password);

        $entityManager->beginTransaction();
        $user = $this->handle($createUser);
        $this->handle(new UpdatePassword($user, $registerRequest->password));

        $entityManager->flush();
        $entityManager->commit();

        return $this->json([
            'message' => 'Uživatel byl úspěšně zaregistrován',
            'email' => $registerRequest->email
        ], Response::HTTP_CREATED);
    }
}
