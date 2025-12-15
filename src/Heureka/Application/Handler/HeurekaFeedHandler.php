<?php

namespace App\Heureka\Application\Handler;

use App\Entity\HeurekaFeed;
use App\Heureka\Application\Action\CreateFeedAction;
use App\Heureka\Application\Action\SyncFeedAction;
use App\Heureka\Application\Action\UpdateFeedAction;
use App\Heureka\Domain\Exception\FeedNotFoundException;
use App\Heureka\Domain\Service\HeurekaFeedService;
use App\Heureka\Domain\Service\XmlParserService;
use App\Lib\ViolationsTrait;
use App\Repository\HeurekaFeedRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

readonly class HeurekaFeedHandler
{
    use ViolationsTrait;

    public function __construct(
        private HeurekaFeedService $feedService,
        private XmlParserService $xmlParserService,
        private HeurekaFeedRepository $feedRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    #[AsMessageHandler]
    public function onFeedCreate(CreateFeedAction $action): HeurekaFeed
    {
        try {
            $feed = $this->feedService->createFeed($action);

            $this->entityManager->persist($feed);
            $this->entityManager->flush();

            $this->logger->info('Heureka feed byl vytvořen', [
                'feed_id' => $feed->getId(),
                'name' => $feed->getName(),
            ]);

            return $feed;
        } catch (\Exception $e) {
            $this->createFieldValidationFailedException(
                'Chyba při vytváření feedu: ' . $e->getMessage(),
                'url'
            );
        }
    }

    #[AsMessageHandler]
    public function onFeedUpdate(UpdateFeedAction $action): HeurekaFeed
    {
        try {
            $feed = $this->feedService->updateFeed($action);

            $this->entityManager->flush();

            $this->logger->info('Heureka feed byl aktualizován', [
                'feed_id' => $feed->getId(),
            ]);

            return $feed;
        } catch (FeedNotFoundException $e) {
            $this->createFieldValidationFailedException(
                'Feed nebyl nalezen.',
                'id'
            );
        }
    }

    #[AsMessageHandler]
    public function onFeedSync(SyncFeedAction $action): array
    {
        try {
            $feed = $this->feedRepository->find($action->feedId);

            if (!$feed) {
                throw new FeedNotFoundException('Feed nenalezen');
            }

            $stats = $this->xmlParserService->parseFeedAndSync($feed);

            $this->logger->info('Heureka feed byl synchronizován', [
                'feed_id' => $feed->getId(),
                'stats' => $stats,
            ]);

            return $stats;
        } catch (\Exception $e) {
            $this->logger->error('Chyba při synchronizaci feedu', [
                'feed_id' => $action->feedId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
