<?php

namespace App\Heureka\Domain\Service;

use App\Entity\Category;
use App\Entity\HeurekaFeed;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class XmlParserService
{
    public function __construct(
        private CategoryRepository $categoryRepository,
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {}

    /**
     * Parsuje Heureka XML feed a vytvoří/aktualizuje produkty
     */
    public function parseFeedAndSync(HeurekaFeed $feed): array
    {
        // Zvýšit memory limit pro sync velkých feedů
        ini_set('memory_limit', '512M');

        $stats = ['created' => 0, 'updated' => 0, 'errors' => 0];
        $batchSize = 50; // Flush každých 50 produktů
        $counter = 0;

        try {
            // Stáhnout XML
            $xmlContent = $this->fetchXmlContent($feed->getUrl());

            // Parsovat XML
            $xml = new \SimpleXMLElement($xmlContent);

            // Iterovat přes SHOPITEM elementy
            foreach ($xml->SHOPITEM as $shopItem) {
                try {
                    $this->processShopItem($feed, $shopItem, $stats);
                    $counter++;

                    // Flush každých X produktů
                    if ($counter % $batchSize === 0) {
                        $this->entityManager->flush();
                        $this->logger->info("Zpracováno {$counter} produktů, pamět: " . memory_get_usage(true) / 1024 / 1024 . " MB");
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->logger->error('Chyba při zpracování produktu', [
                        'item_id' => (string) ($shopItem->ITEM_ID ?? 'unknown'),
                        'error' => $e->getMessage(),
                    ]);

                    // Pokračovat i přes chybu
                    continue;
                }
            }

            // Finální flush pro zbývající produkty
            $this->entityManager->flush();

            // Refresh feed entity to get accurate count
            $this->entityManager->refresh($feed);

            // Aktualizovat počet produktů a čas synchronizace
            $productCount = $this->productRepository->countByFeed($feed);
            $feed->setProductCount($productCount);
            $feed->setLastSyncedAt(new \DateTime());

            $this->entityManager->flush();

            $this->logger->info("Feed product count updated", ['feed_id' => $feed->getId(), 'count' => $productCount]);

            $this->logger->info("Synchronizace dokončena: {$stats['created']} vytvořeno, {$stats['updated']} aktualizováno, {$stats['errors']} chyb");

        } catch (\Exception $e) {
            $this->logger->error('Chyba při parsování XML feedu', [
                'feed_id' => $feed->getId(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $stats;
    }

    private function fetchXmlContent(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; HeurekaFeedParser/1.0)'
            ]
        ]);

        $content = @file_get_contents($url, false, $context);

        if ($content === false) {
            throw new \RuntimeException('Nepodařilo se stáhnout XML feed z URL: ' . $url);
        }

        return $content;
    }

    private function processShopItem(HeurekaFeed $feed, \SimpleXMLElement $shopItem, array &$stats): void
    {
        $itemId = (string) $shopItem->ITEM_ID;

        // Zkontrolovat, zda produkt již existuje
        $product = $this->productRepository->findByFeedAndItemId($feed, $itemId);

        if ($product) {
            // Update existujícího produktu
            $this->updateProduct($product, $shopItem);
            $stats['updated']++;
        } else {
            // Vytvoření nového produktu
            $product = $this->createProduct($feed, $shopItem);
            $this->entityManager->persist($product);
            $stats['created']++;
        }
    }

    private function createProduct(HeurekaFeed $feed, \SimpleXMLElement $shopItem): Product
    {
        $product = new Product(
            $feed,
            (string) $shopItem->ITEM_ID,
            (string) $shopItem->PRODUCTNAME,
            $this->normalizePrice((string) $shopItem->PRICE_VAT),
            (string) $shopItem->URL
        );

        $this->populateProductFields($product, $shopItem);

        return $product;
    }

    private function updateProduct(Product $product, \SimpleXMLElement $shopItem): void
    {
        $product->setProductName((string) $shopItem->PRODUCTNAME);
        $product->setPriceVat($this->normalizePrice((string) $shopItem->PRICE_VAT));
        $product->setUrl((string) $shopItem->URL);

        $this->populateProductFields($product, $shopItem);
    }

    private function populateProductFields(Product $product, \SimpleXMLElement $shopItem): void
    {
        // Základní pole
        if (isset($shopItem->DESCRIPTION)) {
            $product->setDescription((string) $shopItem->DESCRIPTION);
        }

        if (isset($shopItem->IMGURL)) {
            $product->setImgUrl((string) $shopItem->IMGURL);
        }

        if (isset($shopItem->IMGURL_ALTERNATIVE)) {
            $product->setImgUrlAlternative((string) $shopItem->IMGURL_ALTERNATIVE);
        }

        if (isset($shopItem->MANUFACTURER)) {
            $product->setManufacturer((string) $shopItem->MANUFACTURER);
        }

        if (isset($shopItem->EAN)) {
            $product->setEan((string) $shopItem->EAN);
        }

        if (isset($shopItem->PRODUCTNO)) {
            $product->setProductNo((string) $shopItem->PRODUCTNO);
        }

        // Zpracování kategorie
        if (isset($shopItem->CATEGORYTEXT)) {
            $category = $this->processCategoryText((string) $shopItem->CATEGORYTEXT);
            $product->setCategory($category);
        }
    }

    /**
     * Zpracuje hierarchii kategorie z CATEGORYTEXT
     * Formát: "LEPICÍ PÁSKY | Lepicí pásky balicí"
     */
    private function processCategoryText(string $categoryText): ?Category
    {
        if (empty($categoryText)) {
            return null;
        }

        $parts = array_map('trim', explode('|', $categoryText));

        if (empty($parts)) {
            return null;
        }

        // Zkusit najít existující kategorii podle fullPath
        $category = $this->categoryRepository->findByFullPath($categoryText);

        if ($category) {
            $category->incrementProductCount();
            return $category;
        }

        // Vytvořit kategorii (nebo hierarchii kategorií)
        $parent = null;
        $currentPath = '';

        foreach ($parts as $index => $partName) {
            $currentPath = $index === 0 ? $partName : $currentPath . ' | ' . $partName;

            $existingCategory = $this->categoryRepository->findByFullPath($currentPath);

            if ($existingCategory) {
                $parent = $existingCategory;
            } else {
                $newCategory = new Category($partName, $currentPath, $parent);
                $this->entityManager->persist($newCategory);
                $parent = $newCategory;
            }
        }

        if ($parent) {
            $parent->incrementProductCount();
        }

        return $parent;
    }

    /**
     * Normalizuje cenu z českého formátu (123,45) na MySQL decimal formát (123.45)
     */
    private function normalizePrice(string $price): string
    {
        // Odstraní mezery a nahradí čárku tečkou
        $normalized = str_replace([' ', ','], ['', '.'], trim($price));

        // Ověří, že je to validní číslo
        if (!is_numeric($normalized)) {
            $this->logger->warning('Nevalidní formát ceny', ['original' => $price, 'normalized' => $normalized]);
            return '0.00';
        }

        return $normalized;
    }
}
