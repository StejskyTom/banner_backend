<?php

namespace App\Heureka\Application\Controller;

use App\Entity\HeurekaFeed;
use App\Entity\User;
use App\Heureka\Application\Action\UpdateProductSelectionAction;
use App\Heureka\Infrastructure\DTO\Request\UpdateProductSelectionRequest;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/heureka')]
class ProductController extends AbstractController
{
    use HandleTrait;

    public function __construct(
        MessageBusInterface $messageBus,
        private ProductRepository $productRepository,
        private CategoryRepository $categoryRepository,
    ) {
        $this->messageBus = $messageBus;
    }

    #[Route('/feeds/{id}/products', name: 'api_heureka_products_list', methods: ['GET'])]
    public function listProducts(
        #[MapEntity] HeurekaFeed $feed,
        Request $request
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || $feed->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $search = $request->query->get('search');
        $category = $request->query->get('category');
        $limit = (int) $request->query->get('limit', 100);
        $offset = (int) $request->query->get('offset', 0);
        $sort = $request->query->get('sort', 'name_asc');

        $products = $this->productRepository->findByFeedWithFilters(
            $feed,
            $search,
            $category,
            $limit,
            $offset,
            $sort
        );

        $total = $this->productRepository->countByFeedWithFilters($feed, $search, $category);

        return $this->json([
            'products' => $products,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ], 200, [], ['groups' => ['product:read']]);
    }

    #[Route('/feeds/{id}/categories', name: 'api_heureka_categories_list', methods: ['GET'])]
    public function listCategories(#[MapEntity] HeurekaFeed $feed): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || $feed->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        // Get categories for this specific feed
        $categories = $this->categoryRepository->findCategoriesForFeed($feed);

        return $this->json($categories);
    }

    #[Route('/feeds/{id}/products/selection', name: 'api_heureka_products_update_selection', methods: ['PUT'])]
    public function updateProductSelection(
        #[MapEntity] HeurekaFeed $feed,
        #[MapRequestPayload] UpdateProductSelectionRequest $request
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();

        if ($feed->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $action = new UpdateProductSelectionAction(
            feedId: (string) $feed->getId(),
            selectedProductIds: $request->selectedProductIds
        );

        $this->handle($action);

        return $this->json(['message' => 'Výběr produktů byl aktualizován']);
    }

    #[Route('/feeds/{id}/products/selected', name: 'api_heureka_products_get_selected', methods: ['GET'])]
    public function getSelectedProducts(#[MapEntity] HeurekaFeed $feed): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user || $feed->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $products = $this->productRepository->findSelectedByFeed($feed);

        return $this->json($products, 200, [], ['groups' => ['product:read']]);
    }

    #[Route('/products/search', name: 'api_heureka_products_search', methods: ['GET'])]
    public function searchProducts(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $search = $request->query->get('search');
        $limit = (int) $request->query->get('limit', 10);

        if (!$search || strlen($search) < 3) {
            return $this->json([], 200);
        }

        $products = $this->productRepository->searchByUser($user, $search, $limit);

        return $this->json(['products' => $products], 200, [], ['groups' => ['product:read']]);
    }
}
