<?php

namespace App\Heureka\Application\Handler;

use App\Heureka\Application\Action\UpdateProductSelectionAction;
use App\Heureka\Domain\Service\ProductService;
use App\Lib\ViolationsTrait;
use App\Repository\HeurekaFeedRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

readonly class ProductSelectionHandler
{
    use ViolationsTrait;

    public function __construct(
        private ProductService $productService,
        private HeurekaFeedRepository $feedRepository,
        private LoggerInterface $logger
    ) {}

    #[AsMessageHandler]
    public function onUpdateSelection(UpdateProductSelectionAction $action): void
    {
        try {
            $feed = $this->feedRepository->find($action->feedId);

            if (!$feed) {
                $this->createFieldValidationFailedException('Feed nenalezen', 'feedId');
            }

            $this->productService->updateProductSelection($feed, $action->selectedProductIds);

            $this->logger->info('Product selection aktualizována', [
                'feed_id' => $action->feedId,
                'count' => count($action->selectedProductIds),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Chyba při aktualizaci výběru produktů', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
