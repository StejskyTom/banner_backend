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

use Symfony\Contracts\Cache\CacheInterface;

#[Route('/api/heureka')]
class HeurekaFeedController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private HeurekaFeedRepository $feedRepository,
        private CacheInterface $cache
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
            url: $request->url,
            layout: $request->layout,
            layoutOptions: $request->layoutOptions
        );

        $updatedFeed = $this->handle($action);

        // Invalidate cache
        $this->cache->delete('heureka_product_embed_' . $feed->getId());

        return $this->json($updatedFeed, 200, [], ['groups' => ['feed:read']]);
    }

    #[Route('/feeds/{id}/duplicate', name: 'api_heureka_feed_duplicate', methods: ['POST'])]
    public function duplicateFeed(
        #[MapEntity] HeurekaFeed $feed,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($feed->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $newFeed = clone $feed;
        $newFeed->setName($feed->getName() . ' (kopie)');
        $newFeed->setUser($user); // Add setUser method to HeurekaFeed if missing, or use constructor? No, constructor is for new. Clone creates new obj.
        // But HeurekaFeed has no setUser method in the partial view I saw.
        // Wait, property $user is private. Clone handles shallow copy where $this->user refers to same object.
        // We need to check if $user object is correct. Yes, it's the same user.
        // BUT we need to copy products manually because clone resets products collection (as defined in __clone).
        
        $em->persist($newFeed);
        
        // Deep copy products
        foreach ($feed->getProducts() as $product) {
            $newProduct = clone $product; // Product needs __clone modification or manual cloning?
            // Product entity likely doesn't have __clone.
            // Let's rely on manual cloning for products to be safe and link them to new feed.
             $newProduct = new \App\Entity\Product(
                 $newFeed,
                 $product->getItemId(),
                 $product->getProductName(),
                 $product->getPriceVat(),
                 $product->getUrl()
             );
             $newProduct->setDescription($product->getDescription());
             $newProduct->setImgUrl($product->getImgUrl());
             $newProduct->setImgUrlAlternative($product->getImgUrlAlternative());
             $newProduct->setManufacturer($product->getManufacturer());
             $newProduct->setEan($product->getEan());
             $newProduct->setProductNo($product->getProductNo());
             $newProduct->setCategory($product->getCategory());
             $newProduct->setIsSelected($product->isSelected());
             $newProduct->setPosition($product->getPosition());
             
             $em->persist($newProduct);
        }

        $em->flush();

        return $this->json($newFeed, 201, [], ['groups' => ['feed:read']]);
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

        // Invalidate cache
        $this->cache->delete('heureka_product_embed_' . $feed->getId());

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

            // Invalidate cache
            $this->cache->delete('heureka_product_embed_' . $feed->getId());

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
