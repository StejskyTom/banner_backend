<?php

namespace App\Author\Application\Controller;

use App\Entity\AuthorWidget;
use App\Repository\AuthorWidgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/author-widgets')]
class AuthorWidgetController extends AbstractController
{
    public function __construct(
        private AuthorWidgetRepository $repository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private CacheInterface $cache
    ) {}

    #[Route('', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        $widgets = $this->repository->findBy(['user' => $user], ['createdAt' => 'DESC']);

        return $this->json($widgets, 200, [], ['groups' => 'author_widget:read']);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $widget = new AuthorWidget();
        $widget->setName($data['name'] ?? 'Nový Autor');
        $widget->setUser($this->getUser());
        
        // Optional initial data
        if (isset($data['authorName'])) $widget->setAuthorName($data['authorName']);
        if (isset($data['authorTitle'])) $widget->setAuthorTitle($data['authorTitle']);
        if (isset($data['authorBio'])) $widget->setAuthorBio($data['authorBio']);
        if (isset($data['authorPhotoUrl'])) $widget->setAuthorPhotoUrl($data['authorPhotoUrl']);
        if (isset($data['layout'])) $widget->setLayout($data['layout']);
        if (isset($data['backgroundColor'])) $widget->setBackgroundColor($data['backgroundColor']);
        if (isset($data['borderRadius'])) $widget->setBorderRadius((int)$data['borderRadius']);
        if (isset($data['nameColor'])) $widget->setNameColor($data['nameColor']);
        if (isset($data['bioColor'])) $widget->setBioColor($data['bioColor']);
        if (isset($data['titleColor'])) $widget->setTitleColor($data['titleColor']);
        if (array_key_exists('settings', $data)) $widget->setSettings($data['settings']);

        $this->entityManager->persist($widget);
        $this->entityManager->flush();

        return $this->json($widget, 201, [], ['groups' => 'author_widget:read']);
    }

    #[Route('/{id}/duplicate', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function duplicate(AuthorWidget $widget): JsonResponse
    {
        if ($widget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $newWidget = clone $widget;
        $newWidget->setName($widget->getName() . ' (kopie)');
        $newWidget->setUser($this->getUser());

        $this->entityManager->persist($newWidget);
        $this->entityManager->flush();

        return $this->json($newWidget, 201, [], ['groups' => 'author_widget:read']);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(AuthorWidget $widget): JsonResponse
    {
        if ($widget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->json($widget, 200, [], ['groups' => 'author_widget:read']);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(Request $request, AuthorWidget $widget): JsonResponse
    {
        if ($widget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) $widget->setName($data['name']);
        if (isset($data['authorName'])) $widget->setAuthorName($data['authorName']);
        if (isset($data['authorTitle'])) $widget->setAuthorTitle($data['authorTitle']);
        if (isset($data['authorBio'])) $widget->setAuthorBio($data['authorBio']);
        if (isset($data['authorPhotoUrl'])) $widget->setAuthorPhotoUrl($data['authorPhotoUrl']);
        if (isset($data['layout'])) $widget->setLayout($data['layout']);
        if (isset($data['backgroundColor'])) $widget->setBackgroundColor($data['backgroundColor']);
        if (isset($data['borderRadius'])) $widget->setBorderRadius((int)$data['borderRadius']);
        if (isset($data['nameColor'])) $widget->setNameColor($data['nameColor']);
        if (isset($data['bioColor'])) $widget->setBioColor($data['bioColor']);
        if (isset($data['titleColor'])) $widget->setTitleColor($data['titleColor']);
        if (array_key_exists('settings', $data)) $widget->setSettings($data['settings']);
        
        $widget->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        // Invalidate cache
        $this->cache->delete('author_widget_embed_' . $widget->getId());

        return $this->json($widget, 200, [], ['groups' => 'author_widget:read']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(AuthorWidget $widget): JsonResponse
    {
        if ($widget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($widget);
        $this->entityManager->flush();

        // Invalidate cache
        $this->cache->delete('author_widget_embed_' . $widget->getId());

        return $this->json(null, 204);
    }

    #[Route('/{id}/embed.js', methods: ['GET'])]
    public function embed(Request $request, AuthorWidget $widget): Response
    {
        $response = new Response();
        $response->setLastModified($widget->getUpdatedAt());
        $response->setPublic();
        
        // Check if the response has not been modified
        if ($response->isNotModified($request)) {
            return $response;
        }

        $content = $this->cache->get('author_widget_embed_' . $widget->getId(), function (ItemInterface $item) use ($widget) {
            $item->expiresAfter(3600 * 24); // Server cache for 24 hours
            
            return $this->renderView('author-embed.js.twig', [
                'widget' => $widget,
            ]);
        });

        $response->setContent($content);
        $response->headers->set('Content-Type', 'application/javascript');
        // Force browser to revalidate with server every time
        $response->headers->set('Cache-Control', 'public, no-cache');
        
        return $response;
    }

    #[Route('/{id}/upload', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function uploadPhoto(
        Request $request, 
        AuthorWidget $widget,
        #[Autowire('%server_domain%')] string $server_domain = ''
    ): JsonResponse
    {
        if ($widget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'Nebyl nahrán žádný soubor'], 400);
        }

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

        $dir = $this->getParameter('kernel.project_dir') . '/' . $publicDir . '/uploads/authors';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $ext = $file->guessExtension() ?: 'bin';
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $file->move($dir, $filename);

        $publicPath = '/uploads/authors/' . $filename;
        $publicUrl = $server_domain . $publicPath;

        $widget->setAuthorPhotoUrl($publicUrl);
        $widget->setUpdatedAt(new \DateTimeImmutable());
        
        $this->entityManager->flush();

        // Invalidate cache
        $this->cache->delete('author_widget_embed_' . $widget->getId());

        return $this->json([
            'url' => $publicUrl
        ]);
    }
}
