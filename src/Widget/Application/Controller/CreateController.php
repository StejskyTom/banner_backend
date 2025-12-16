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
use App\Widget\Application\Action\CreateWidgetAction;
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

    #[Route('/widgets/logo-carousel/new', name: 'api_widgets_new', methods: ['POST'])]
    public function createWidget(
        #[MapRequestPayload] CreateWidgetAction $action,
        EntityManagerInterface $em
    ): JsonResponse {
        $widget = $this->handle($action);
        $em->persist($widget);
        $em->flush();

        return $this->json($widget, 200, [], ['groups' => ['widget:read']]);
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
            'speed' => $widget->getSpeed(),
            'imageSize' => $widget->getImageSize(),
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

        // Získání dat z requestu
        $data = json_decode($request->getContent(), true);
        $file = $request->files->get('file');
        $externalUrl = $data['url'] ?? null;

        // Kontrola, že je zadaný buď soubor nebo URL
        if (!$file && !$externalUrl) {
            return $this->json(['error' => 'Musíte zadat buď soubor nebo URL'], 400);
        }

        // Pokud jsou zadané oba, preferuj soubor
        if ($file && $externalUrl) {
            return $this->json(['error' => 'Zadejte buď soubor nebo URL, ne obojí'], 400);
        }

        try {
            $attachment = null;
            $publicUrl = '';
            $isExternal = false;

            if ($file) {
                // Zpracování nahraného souboru (stávající logika)

                // Validace typu/velikosti
                if (!in_array($file->getMimeType(), ['image/png','image/jpeg','image/webp','image/svg+xml'])) {
                    return $this->json(['error' => 'Nepovolený typ souboru'], 400);
                }
                if ($file->getSize() > 5 * 1024 * 1024) {
                    return $this->json(['error' => 'Soubor je příliš velký (max 5MB)'], 400);
                }

                $publicDir = $this->getParameter('app.public_dir');
                
                // Auto-detection for shared hosting (Endora)
                if ($publicDir === 'public' && is_dir($this->getParameter('kernel.project_dir') . '/public_html')) {
                    $publicDir = 'public_html';
                }

                $dir = $this->getParameter('kernel.project_dir') . '/' . $publicDir . '/uploads/logos';
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }

                $ext = $file->guessExtension() ?: 'bin';
                $filename = bin2hex(random_bytes(8)) . '.' . $ext;
                $file->move($dir, $filename);

                $publicPath = '/uploads/logos/' . $filename;
                $publicUrl = $server_domain . $publicPath;
                $isExternal = false;

            } elseif ($externalUrl) {
                // Zpracování externí URL

                // Validace URL
                if (!filter_var($externalUrl, FILTER_VALIDATE_URL)) {
                    return $this->json(['error' => 'Neplatná URL adresa'], 400);
                }

                // Kontrola, že URL je HTTPS nebo HTTP
                $scheme = parse_url($externalUrl, PHP_URL_SCHEME);
                if (!in_array($scheme, ['http', 'https'])) {
                    return $this->json(['error' => 'URL musí začínat http:// nebo https://'], 400);
                }

                // Volitelně: ověření, že URL odkazuje na obrázek
                // (můžete použít get_headers nebo curl pro kontrolu Content-Type)
                $headers = @get_headers($externalUrl, 1);
                if ($headers && isset($headers['Content-Type'])) {
                    $contentType = is_array($headers['Content-Type'])
                        ? $headers['Content-Type'][0]
                        : $headers['Content-Type'];

                    if (!str_starts_with($contentType, 'image/')) {
                        return $this->json(['error' => 'URL neodkazuje na obrázek'], 400);
                    }
                }

                $publicUrl = $externalUrl;
                $isExternal = true;
            }

            // Vypočti next position
            $current = $widget->getAttachments();
            $nextPos = count($current);

            // Vytvoř attachment
            $attachment = new Attachment($widget, $publicUrl);
            $attachment->setPosition($nextPos);
            $attachment->setIsExternal($isExternal);

            $em->persist($attachment);
            $em->flush();

            return $this->json($attachment, 201, [], ['groups' => ['widget:read']]);

        } catch (\Exception $exception) {
            // V produkci použijte logger místo dd()
            return $this->json(['error' => 'Chyba při zpracování: ' . $exception->getMessage()], 500);
        }
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

    #[Route('/widgets/{id}', name: 'api_widget_delete', methods: ['DELETE'])]
    public function widgetDelete(Widget $widget, EntityManagerInterface $em): JsonResponse
    {
        // Ověření, že attachment patří aktuálnímu uživateli
        if ($widget->getUser() !== $this->getUser()) {
            throw new WidgetNotFound('Položka nenalezena');
        }

        $em->remove($widget);
        $em->flush();

        return $this->json([
            'message' => 'Widget byl úspěšně odstraněn',
            'id' => $widget->getId(),
        ]);
    }

}
