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
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api')]
class CreateController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private WidgetRepository $widgetRepository,
        private CacheInterface $cache
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

        // Invalidate cache
        $this->cache->delete('widget_embed_' . $widget->getId());

        return $this->json($widget, 200, [], ['groups' => ['widget:read']]);
    }

    #[Route('/widget/{widgetId}/embed.js', name: 'widget_embed')]
    public function generateWidgetJS(
        Request $request,
        string $widgetId,
        SerializerInterface $serializer
    ): Response
    {
        /** @var Widget $widget */
        $widget = $this->widgetRepository->find($widgetId);

        if (!$widget) {
            return new Response('// Widget not found', 404, ['Content-Type' => 'application/javascript']);
        }

        $response = new Response();
        $response->setLastModified($widget->getUpdatedAt());
        $response->setPublic();

        // Check if the response has not been modified
        if ($response->isNotModified($request)) {
            return $response;
        }

        $content = $this->cache->get('widget_embed_' . $widgetId, function (ItemInterface $item) use ($widget, $serializer) {
            $item->expiresAfter(3600 * 24); // Server cache for 24 hours

            $attachments = $widget->getAttachments();

            $attachmentsJson = $serializer->serialize(
                $attachments,
                'json',
                [
                    'groups' => ['widget:read'],
                    'json_encode_options' => JSON_UNESCAPED_SLASHES
                ]
            );

            $settings = $widget->getSettings() ?? [];
            // Construct Title HTML from settings
            $titleTag = $settings['titleTag'] ?? 'h2';
            $titleText = $widget->getTitle() ?? $settings['titleText'] ?? '';
            $titleStyle = [];
            if (!empty($settings['titleColor'])) $titleStyle[] = "color: {$settings['titleColor']}";
            if (!empty($settings['titleFont'])) $titleStyle[] = "font-family: {$settings['titleFont']}";
            if (!empty($settings['titleSize'])) $titleStyle[] = "font-size: {$settings['titleSize']}";
            if (!empty($settings['titleAlign'])) $titleStyle[] = "text-align: {$settings['titleAlign']}";
            if (!empty($settings['titleBold']) && $settings['titleBold']) $titleStyle[] = "font-weight: bold";
            if (!empty($settings['titleItalic']) && $settings['titleItalic']) $titleStyle[] = "font-style: italic";
            $marginBottom = $settings['titleMarginBottom'] ?? 12;
            $titleStyle[] = "margin: 0 0 {$marginBottom}px"; 
            
            $titleStyleStr = implode(';', $titleStyle);
            $titleHtml = "<{$titleTag} style='{$titleStyleStr}'>{$titleText}</{$titleTag}>";

            // Construct Subtitle HTML from settings
            $subtitleTag = $settings['subtitleTag'] ?? 'p';
            $subtitleText = $settings['subtitleText'] ?? '';
            
            if (!empty($subtitleText)) {
                $subStyle = [];
                if (!empty($settings['subtitleColor'])) $subStyle[] = "color: {$settings['subtitleColor']}";
                if (!empty($settings['subtitleFont'])) $subStyle[] = "font-family: {$settings['subtitleFont']}";
                if (!empty($settings['subtitleSize'])) $subStyle[] = "font-size: {$settings['subtitleSize']}";
                if (!empty($settings['subtitleAlign'])) $subStyle[] = "text-align: {$settings['subtitleAlign']}";
                if (!empty($settings['subtitleBold']) && $settings['subtitleBold']) $subStyle[] = "font-weight: bold";
                if (!empty($settings['subtitleItalic']) && $settings['subtitleItalic']) $subStyle[] = "font-style: italic";
                $subMargin = $settings['subtitleMarginBottom'] ?? 24;
                $subStyle[] = "margin: 0 0 {$subMargin}px";
                
                $subStyleStr = implode(';', $subStyle);
                $titleHtml .= "<{$subtitleTag} style='{$subStyleStr}'>{$subtitleText}</{$subtitleTag}>";
            }

            return $this->renderView('embed.js.twig', [
                'widgetId' => $widget->getId(),
                'title' => $titleHtml,
                'attachments' => $attachmentsJson,
                'speed' => $widget->getSpeed(),
                'imageSize' => $widget->getImageSize(),
                'pauseOnHover' => $widget->getPauseOnHover(),
                'gap' => $widget->getGap(),
                'settings' => $settings,
            ]);
        });

        $response->setContent($content);
        $response->headers->set('Content-Type', 'application/javascript');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        // Force browser to revalidate with server every time
        $response->headers->set('Cache-Control', 'public, no-cache');

        return $response;
    }

    #[Route('/widgets/{id}/attachments', methods: ['POST'])]
    public function uploadAttachment(
        #[MapEntity] Widget $widget,
        Request $request,
        EntityManagerInterface $em
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

                $publicDir = 'public';
                if ($this->getParameter('kernel.project_dir') && is_dir($this->getParameter('kernel.project_dir') . '/public_html')) {
                     // Fallback for Endora hosting if needed, but prioritize specific config if exists
                     // For local dev, standard public is best.
                     // Checks if .env has APP_PUBLIC_DIR
                     try {
                        $publicDir = $this->getParameter('app.public_dir');
                     } catch(\Exception $e) {
                        $publicDir = 'public';
                     }
                }

                $dir = $this->getParameter('kernel.project_dir') . '/' . $publicDir . '/uploads/logos';
                if (!is_dir($dir)) {
                    @mkdir($dir, 0775, true);
                }

                $ext = $file->guessExtension() ?: 'bin';
                $filename = bin2hex(random_bytes(8)) . '.' . $ext;
                $file->move($dir, $filename);

                $publicPath = '/uploads/logos/' . $filename;
                // Use the request host to form the URL, ensuring it matches the server handling the request
                $publicUrl = $request->getSchemeAndHttpHost() . $publicPath;
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

            // Invalidate cache
            $this->cache->delete('widget_embed_' . $widget->getId());

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

        // Invalidate cache
        $this->cache->delete('widget_embed_' . $widget->getId());

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

        // Invalidate cache
        $this->cache->delete('widget_embed_' . $widget->getId());

        return $this->json([
            'message' => 'Widget byl úspěšně odstraněn',
            'id' => $widget->getId(),
        ]);
    }

}
