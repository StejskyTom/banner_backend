<?php
// src/Controller/Api/RegistrationController.php
namespace App\Widget\Application\Controller;

use App\Entity\Attachment;
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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

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

        return $this->json($widget, 200, [], ['groups' => ['widget:read']]);
    }

    #[Route('/widget/{widgetId}/embed.js', name: 'widget_embed')]
    public function generateWidgetJS(
        string $widgetId,
        SerializerInterface $serializer
    ): Response
    {
        /** @var Widget $widget */
        $widget = $this->widgetRepository->find($widgetId);
        $attachments = $widget->getAttachments();

        $attachmentsJson = $serializer->serialize(
            $attachments,
            'json',
            [
                'groups' => ['widget:read'],
                'json_encode_options' => JSON_UNESCAPED_SLASHES
            ]
        );

        $jsTemplate = $this->renderView('embed.js.twig', [
            'widgetId' => $widgetId,
            'title' => $widget->getTitle(),
            'attachments' => $attachmentsJson,
        ]);

        $response = new Response($jsTemplate);
        $response->headers->set('Content-Type', 'application/javascript');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Cache-Control', 'public, max-age=300'); // 5min cache

        return $response;
    }

    #[Route('/widgets/{id}/attachments', methods: ['POST'])]
    public function uploadAttachment(
        #[MapEntity] Widget $widget,
        Request $request,
        EntityManagerInterface $em,
        #[Autowire('%server_domain%')]
        string $server_domain = ''
    ): JsonResponse {
        if ($widget->getUser() !== $this->getUser()) {
            return $this->json(['error' => 'Widget nenalezen'], 404);
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'Soubor "file" je povinný'], 400);
        }

        // Validace typu/velikosti
        if (!in_array($file->getMimeType(), ['image/png','image/jpeg','image/webp','image/svg+xml'])) {
            return $this->json(['error' => 'Nepovolený typ souboru'], 400);
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->json(['error' => 'Soubor je příliš velký (max 5MB)'], 400);
        }

        try {
            $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/logos';
            if (!is_dir($dir)) { @mkdir($dir, 0775, true); }

            $ext = $file->guessExtension() ?: 'bin';
            $filename = bin2hex(random_bytes(8)) . '.' . $ext;
            $file->move($dir, $filename);

            $publicPath = '/uploads/logos/' . $filename;

            // vypočti next position
            $current = $widget->getAttachments();
            $nextPos = count($current);

            $publicUrl = $server_domain . $publicPath;

            $attachment = new Attachment($widget, $publicUrl);
            $attachment->setPosition($nextPos);

            $em->persist($attachment);
            $em->flush();

        } catch (\Exception $exception) {
            dd($exception);
        }


        return $this->json($attachment, 201, [], ['groups' => ['widget:read']]);
    }

    #[Route('/attachments/{id}', name: 'api_attachments_delete', methods: ['DELETE'])]
    public function deleteAttachment(Attachment $attachment, EntityManagerInterface $em): JsonResponse
    {
        $widget = $attachment->getWidget();

        // Ověření, že attachment patří aktuálnímu uživateli
        if ($widget->getUser() !== $this->getUser()) {
            throw new WidgetNotFound('Položka nenalezena');
        }

        $em->remove($attachment);
        $em->flush();

        return $this->json([
            'message' => 'Attachment byl úspěšně odstraněn',
            'id' => $attachment->getId(),
        ]);
    }

}
