<?php

namespace App\Faq\Application\Controller;

use App\Entity\FaqWidget;
use App\Repository\FaqWidgetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/api/faq-widgets')]
class FaqWidgetController extends AbstractController
{
    public function __construct(
        private FaqWidgetRepository $repository,
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

        return $this->json($widgets, 200, [], ['groups' => 'faq_widget:read']);
    }

    #[Route('', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $widget = new FaqWidget();
        $widget->setName($data['name'] ?? 'Nový FAQ');
        $widget->setUser($this->getUser());
        $widget->setQuestions($data['questions'] ?? []);
        $widget->setFont($data['font'] ?? null);
        $widget->setQuestionColor($data['questionColor'] ?? null);
        $widget->setAnswerColor($data['answerColor'] ?? null);
        $widget->setHoverColor($data['hoverColor'] ?? null);
        $widget->setBackgroundColor($data['backgroundColor'] ?? null);

        $this->entityManager->persist($widget);
        $this->entityManager->flush();

        return $this->json($widget, 201, [], ['groups' => 'faq_widget:read']);
    }

    #[Route('/{id}', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(FaqWidget $widget): JsonResponse
    {
        if ($widget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->json($widget, 200, [], ['groups' => 'faq_widget:read']);
    }

    #[Route('/{id}', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function update(Request $request, FaqWidget $widget): JsonResponse
    {
        if ($widget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['name'])) {
            $widget->setName($data['name']);
        }
        if (isset($data['questions'])) {
            $widget->setQuestions($data['questions']);
        }
        if (isset($data['font'])) {
            $widget->setFont($data['font']);
        }
        if (isset($data['questionColor'])) {
            $widget->setQuestionColor($data['questionColor']);
        }
        if (isset($data['answerColor'])) {
            $widget->setAnswerColor($data['answerColor']);
        }
        if (isset($data['hoverColor'])) {
            $widget->setHoverColor($data['hoverColor']);
        }
        if (isset($data['backgroundColor'])) {
            $widget->setBackgroundColor($data['backgroundColor']);
        }
        
        $widget->setUpdatedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        // Invalidate cache
        $this->cache->delete('faq_widget_embed_' . $widget->getId());

        return $this->json($widget, 200, [], ['groups' => 'faq_widget:read']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(FaqWidget $widget): JsonResponse
    {
        if ($widget->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $this->entityManager->remove($widget);
        $this->entityManager->flush();

        // Invalidate cache
        $this->cache->delete('faq_widget_embed_' . $widget->getId());

        return $this->json(null, 204);
    }

    #[Route('/{id}/embed.js', methods: ['GET'])]
    public function embed(Request $request, FaqWidget $widget): Response
    {
        $response = new Response();
        $response->setLastModified($widget->getUpdatedAt());
        $response->setPublic();
        
        // Check if the response has not been modified
        if ($response->isNotModified($request)) {
            return $response;
        }

        $content = $this->cache->get('faq_widget_embed_' . $widget->getId(), function (ItemInterface $item) use ($widget) {
            $item->expiresAfter(3600 * 24); // Server cache for 24 hours
            
            return $this->renderView('faq-embed.js.twig', [
                'widget' => $widget,
            ]);
        });

        $response->setContent($content);
        $response->headers->set('Content-Type', 'application/javascript');
        // Force browser to revalidate with server every time
        $response->headers->set('Cache-Control', 'public, no-cache');
        
        return $response;
    }
}
