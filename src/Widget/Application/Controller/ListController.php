<?php
// src/Controller/Api/RegistrationController.php
namespace App\Widget\Application\Controller;

use App\Entity\User;
use App\Entity\Widget;
use App\Repository\WidgetRepository;
use App\User\Application\Action\CreateUserAction;
use App\User\Application\Action\UpdatePassword;
use App\User\Infrastructure\DTO\Request\RegisterRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
class ListController extends AbstractController
{
    use HandleTrait;

    #[Route('/widgets', name: 'api_widget_list', methods: ['GET'])]
    public function widgets(
        WidgetRepository $repository
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user === null) {
            throw $this->createAccessDeniedException();
        }

        $widgets = $repository->findByUser($user);

        return $this->json($widgets, 200, [], ['groups' => ['widget:read']]);
    }

    #[Route('/widgets/{id}', name: 'api_widget_detail', methods: ['GET'])]
    public function detail(
        #[MapEntity] Widget $widget
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user === null || $widget->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return $this->json($widget, 200, [], ['groups' => ['widget:read']]);
    }
}
