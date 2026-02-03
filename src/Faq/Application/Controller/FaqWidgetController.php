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

        // Question styling
        $widget->setQuestionTag($data['questionTag'] ?? null);
        $widget->setQuestionSize($data['questionSize'] ?? null);
        $widget->setQuestionFont($data['questionFont'] ?? null);
        $widget->setQuestionBold($data['questionBold'] ?? null);
        $widget->setQuestionItalic($data['questionItalic'] ?? null);
        $widget->setQuestionAlign($data['questionAlign'] ?? null);
        $widget->setQuestionMarginBottom($data['questionMarginBottom'] ?? null);

        // Answer styling
        $widget->setAnswerTag($data['answerTag'] ?? null);
        $widget->setAnswerSize($data['answerSize'] ?? null);
        $widget->setAnswerFont($data['answerFont'] ?? null);
        $widget->setAnswerBold($data['answerBold'] ?? null);
        $widget->setAnswerItalic($data['answerItalic'] ?? null);
        $widget->setAnswerAlign($data['answerAlign'] ?? null);
        $widget->setAnswerMarginBottom($data['answerMarginBottom'] ?? null);

        // Arrow settings
        $widget->setArrowPosition($data['arrowPosition'] ?? null);
        $widget->setArrowColor($data['arrowColor'] ?? null);
        $widget->setArrowSize($data['arrowSize'] ?? null);

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

        // Question styling
        if (array_key_exists('questionTag', $data)) {
            $widget->setQuestionTag($data['questionTag']);
        }
        if (array_key_exists('questionSize', $data)) {
            $widget->setQuestionSize($data['questionSize']);
        }
        if (array_key_exists('questionFont', $data)) {
            $widget->setQuestionFont($data['questionFont']);
        }
        if (array_key_exists('questionBold', $data)) {
            $widget->setQuestionBold($data['questionBold']);
        }
        if (array_key_exists('questionItalic', $data)) {
            $widget->setQuestionItalic($data['questionItalic']);
        }
        if (array_key_exists('questionAlign', $data)) {
            $widget->setQuestionAlign($data['questionAlign']);
        }
        if (array_key_exists('questionMarginBottom', $data)) {
            $widget->setQuestionMarginBottom($data['questionMarginBottom']);
        }

        // Answer styling
        if (array_key_exists('answerTag', $data)) {
            $widget->setAnswerTag($data['answerTag']);
        }
        if (array_key_exists('answerSize', $data)) {
            $widget->setAnswerSize($data['answerSize']);
        }
        if (array_key_exists('answerFont', $data)) {
            $widget->setAnswerFont($data['answerFont']);
        }
        if (array_key_exists('answerBold', $data)) {
            $widget->setAnswerBold($data['answerBold']);
        }
        if (array_key_exists('answerItalic', $data)) {
            $widget->setAnswerItalic($data['answerItalic']);
        }
        if (array_key_exists('answerAlign', $data)) {
            $widget->setAnswerAlign($data['answerAlign']);
        }
        if (array_key_exists('answerMarginBottom', $data)) {
            $widget->setAnswerMarginBottom($data['answerMarginBottom']);
        }

        // Arrow settings
        if (array_key_exists('arrowPosition', $data)) {
            $widget->setArrowPosition($data['arrowPosition']);
        }
        if (array_key_exists('arrowColor', $data)) {
            $widget->setArrowColor($data['arrowColor']);
        }
        if (array_key_exists('arrowSize', $data)) {
            $widget->setArrowSize($data['arrowSize']);
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
