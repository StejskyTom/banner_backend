<?php

namespace App\Heureka\Application\Controller;

use App\Entity\HeurekaFeed;
use App\Entity\User;
use App\Heureka\Application\Action\CreateFeedAction;
use App\Heureka\Application\Action\SyncFeedAction;
use App\Heureka\Application\Action\UpdateFeedAction;
use App\Heureka\Infrastructure\DTO\Request\CreateFeedRequest;
use App\Heureka\Infrastructure\DTO\Request\UpdateFeedRequest;
use App\Repository\HeurekaFeedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/heureka')]
class HeurekaFeedController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private HeurekaFeedRepository $feedRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/feeds', name: 'api_heureka_feeds_list', methods: ['GET'])]
    public function listFeeds(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $feeds = $this->feedRepository->findByUser($user);

        return $this->json($feeds, 200, [], ['groups' => ['feed:read']]);
    }

    #[Route('/feeds/{id}', name: 'api_heureka_feed_detail', methods: ['GET'])]
    public function feedDetail(#[MapEntity] HeurekaFeed $feed): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || $feed->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        return $this->json($feed, 200, [], ['groups' => ['feed:read']]);
    }

    #[Route('/feeds', name: 'api_heureka_feed_create', methods: ['POST'])]
    public function createFeed(
        #[MapRequestPayload] CreateFeedRequest $request
    ): JsonResponse {
        $action = new CreateFeedAction(
            url: $request->url,
            name: $request->name
        );

        $feed = $this->handle($action);

        return $this->json($feed, 201, [], ['groups' => ['feed:read']]);
    }

    #[Route('/feeds/{id}', name: 'api_heureka_feed_update', methods: ['PUT'])]
    public function updateFeed(
        #[MapEntity] HeurekaFeed $feed,
        #[MapRequestPayload] UpdateFeedRequest $request
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($feed->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $action = new UpdateFeedAction(
            id: (string) $feed->getId(),
            name: $request->name,
            url: $request->url
        );

        $updatedFeed = $this->handle($action);

        return $this->json($updatedFeed, 200, [], ['groups' => ['feed:read']]);
    }

    #[Route('/feeds/{id}', name: 'api_heureka_feed_delete', methods: ['DELETE'])]
    public function deleteFeed(
        #[MapEntity] HeurekaFeed $feed,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($feed->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($feed);
        $em->flush();

        return $this->json([
            'message' => 'Feed byl úspěšně smazán',
            'id' => $feed->getId(),
        ]);
    }

    #[Route('/feeds/{id}/sync', name: 'api_heureka_feed_sync', methods: ['POST'])]
    public function syncFeed(#[MapEntity] HeurekaFeed $feed): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($feed->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        try {
            $action = new SyncFeedAction(feedId: (string) $feed->getId());
            $stats = $this->handle($action);

            return $this->json([
                'message' => 'Synchronizace proběhla úspěšně',
                'stats' => $stats
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Chyba při synchronizaci: ' . $e->getMessage()
            ], 500);
        }
    }
}
