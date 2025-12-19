<?php

namespace App\Controller;

use App\Entity\ArticleWidget;
use App\Entity\User;
use App\Repository\ArticleWidgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/api/article-widgets')]
class ArticleWidgetController extends AbstractController
{
    public function __construct(
        private ArticleWidgetRepository $repository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer
    ) {}

    #[Route('', name: 'api_article_widgets_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $widgets = $this->repository->findBy(['user' => $user], ['updatedAt' => 'DESC']);

        return $this->json($widgets, Response::HTTP_OK, [], ['groups' => ['article_widget:read']]);
    }

    #[Route('/{id}', name: 'api_article_widgets_show', methods: ['GET'])]
    public function show(ArticleWidget $widget): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($widget->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return $this->json($widget, Response::HTTP_OK, [], ['groups' => ['article_widget:read']]);
    }

    #[Route('', name: 'api_article_widgets_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        
        $widget = new ArticleWidget();
        $widget->setUser($user);
        $widget->setName($data['name'] ?? 'Nový článek');
        $widget->setContent($data['content'] ?? []);

        $this->entityManager->persist($widget);
        $this->entityManager->flush();

        return $this->json($widget, Response::HTTP_CREATED, [], ['groups' => ['article_widget:read']]);
    }

    #[Route('/{id}', name: 'api_article_widgets_update', methods: ['PUT'])]
    public function update(Request $request, ArticleWidget $widget): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($widget->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $widget->setName($data['name']);
        }
        if (isset($data['content'])) {
            $widget->setContent($data['content']);
        }

        $this->entityManager->flush();

        return $this->json($widget, Response::HTTP_OK, [], ['groups' => ['article_widget:read']]);
    }

    #[Route('/{id}', name: 'api_article_widgets_delete', methods: ['DELETE'])]
    public function delete(ArticleWidget $widget): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($widget->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($widget);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{id}/image', name: 'api_article_widgets_upload_image', methods: ['POST'])]
    public function uploadImage(
        Request $request,
        ArticleWidget $widget,
        SluggerInterface $slugger,
        #[Autowire('%server_domain%')] string $server_domain = ''
    ): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if ($widget->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $file = $request->files->get('image');
        if (!$file) {
            return $this->json(['error' => 'No image file provided'], Response::HTTP_BAD_REQUEST);
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $publicDir = $this->getParameter('app.public_dir');
            
            // Auto-detection for shared hosting (Endora)
            if ($publicDir === 'public' && is_dir($this->getParameter('kernel.project_dir') . '/public_html')) {
                $publicDir = 'public_html';
            }

            $dir = $this->getParameter('kernel.project_dir') . '/' . $publicDir . '/uploads/article_images';
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $file->move(
                $dir,
                $newFilename
            );
        } catch (FileException $e) {
            return $this->json(['error' => 'Failed to upload image'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // Return the public URL
        $url = $server_domain . '/uploads/article_images/' . $newFilename;

        return $this->json(['url' => $url]);
    }
}
