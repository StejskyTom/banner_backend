<?php

namespace App\Heureka\Domain\Service;

use App\Entity\HeurekaFeed;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProductService
{
    public function __construct(
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Hromadná aktualizace výběru produktů
     */
    public function updateProductSelection(HeurekaFeed $feed, array $selectedProductIds): void
    {
        // Nejprve odznač všechny produkty daného feedu
        $allProducts = $this->productRepository->findByFeed($feed);

        foreach ($allProducts as $product) {
            $product->setIsSelected(false);
            $product->setPosition(0);
        }

        // Označ vybrané produkty a nastav pozici
        foreach ($selectedProductIds as $position => $productId) {
            $product = $this->productRepository->find($productId);

            if ($product && $product->getFeed() === $feed) {
                $product->setIsSelected(true);
                $product->setPosition($position);
            }
        }

        $this->entityManager->flush();
    }

    /**
     * Získá vybrané produkty pro embed
     */
    public function getSelectedProducts(HeurekaFeed $feed): array
    {
        return $this->productRepository->findSelectedByFeed($feed);
    }
}
