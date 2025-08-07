<?php
// src/Controller/Api/RegistrationController.php
namespace App\Widget\Application\Controller;

use App\Entity\User;
use App\Entity\Widget;
use App\Repository\WidgetRepository;
use App\User\Application\Action\CreateUserAction;
use App\User\Application\Action\UpdatePassword;
use App\User\Infrastructure\DTO\Request\RegisterRequest;
use App\Widget\Application\Action\UpdateWidgetAction;
use App\Widget\Domain\Exception\WidgetNotFound;
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
class CreateController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private WidgetRepository $widgetRepository,
    )
    {
        $this->messageBus = $messageBus;
    }

    #[Route('/widgets/{id}', name: 'api_widgets_update', methods: ['PUT'])]
    public function updateWidget(
        #[MapEntity] Widget $widget,
        #[MapRequestPayload] UpdateWidgetAction $action,
        EntityManagerInterface $em
    ): JsonResponse {
        if (!$widget) {
            throw new WidgetNotFound('Widget nenalezen');
        }

        // Ověření vlastnictví
        if ($widget->getUser() !== $this->getUser()) {
            throw new WidgetNotFound('Widget nenalezen');
        }

        $widget = $this->handle($action);
        $em->flush();

        return $this->json([
            'message' => 'Widget byl úspěšně aktualizován',
            'id' => $widget->getId(),
            'title' => $widget->getTitle(),
            'logos' => $widget->getLogos(),
        ]);
    }

    #[Route('/widget/{widgetId}/embed.js', name: 'widget_embed')]
    public function generateWidgetJS(string $widgetId): Response
    {
        $widget = $this->widgetRepository->find($widgetId);
        $logos = $widget->getLogos();

        $jsTemplate = $this->renderView('embed.js.twig', [
            'widgetId' => $widgetId,
            'title' => $widget->getTitle(),
            'logos' => $logos,
        ]);

        $response = new Response($jsTemplate);
        $response->headers->set('Content-Type', 'application/javascript');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Cache-Control', 'public, max-age=300'); // 5min cache

        return $response;
    }

}
