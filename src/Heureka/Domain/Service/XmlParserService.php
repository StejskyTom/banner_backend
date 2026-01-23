<?php

namespace App\Heureka\Domain\Service;

use App\Entity\Category;
use App\Entity\HeurekaFeed;
use App\Entity\Product;
use App\Heureka\Domain\DTO;
use App\Heureka\Domain\Service\Parser\FeedParserInterface;
use App\Heureka\Domain\Service\Parser;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class XmlParserService
{
    /**
     * @var FeedParserInterface[]
     */
    private array $parsers;

    public function __construct(
        private CategoryRepository $categoryRepository,
        private ProductRepository $productRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger
    ) {
        // Register available parsers
        $this->parsers = [
            new Parser\HeurekaParser(),
            new Parser\GoogleParser(),
        ];
    }

    /**
     * Parsuje Heureka/Google XML feed a vytvoří/aktualizuje produkty
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
            try {
                 $xml = new \SimpleXMLElement($xmlContent);
            } catch (\Exception $e) {
                // Try to handle namespace issues or malformed XML if acceptable,
                // but for now just rethrow
                throw $e;
            }

            // Detect parser
            $parser = $this->detectParser($xml);
            $this->logger->info("Detected parser: " . get_class($parser));

            // Iterovat přes produkty z parseru
            foreach ($parser->parse($xml) as $productData) {
                try {
                    $this->processProductData($feed, $productData, $stats);
                    $counter++;

                    // Flush každých X produktů
                    if ($counter % $batchSize === 0) {
                        $this->entityManager->flush();
                        $this->entityManager->clear(); // Detach objects to free memory
                        // Re-fetch feed to keep it managed
                        $feed = $this->entityManager->find(HeurekaFeed::class, $feed->getId()); 
                        $this->logger->info("Zpracováno {$counter} produktů, pamět: " . memory_get_usage(true) / 1024 / 1024 . " MB");
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    $this->logger->error('Chyba při zpracování produktu', [
                        'item_id' => $productData->itemId ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    // Pokračovat i přes chybu
                    continue;
                }
            }

            // Finální flush pro zbývající produkty
            $this->entityManager->flush();

            // Refresh feed entity/stats
            // Note: After clear(), $feed might be detached, so we fetch it fresh if needed or rely on the id. 
            // Since we re-fetched in the loop, we should be careful. 
            // Safest is to fetch fresh feed instance for final updates.
            $feed = $this->entityManager->find(HeurekaFeed::class, $feed->getId());

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

    private function detectParser(\SimpleXMLElement $xml): FeedParserInterface
    {
        foreach ($this->parsers as $parser) {
            if ($parser->supports($xml)) {
                return $parser;
            }
        }

        throw new \RuntimeException('Nepodporovaný formát feedu. Nebyl nalezen vhodný parser.');
    }

    private function fetchXmlContent(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (compatible; FeedParser/1.0)'
            ]
        ]);

        $content = @file_get_contents($url, false, $context);

        if ($content === false) {
            throw new \RuntimeException('Nepodařilo se stáhnout XML feed z URL: ' . $url);
        }

        return $content;
    }

    private function processProductData(HeurekaFeed $feed, DTO\ProductDataDTO $data, array &$stats): void
    {
        // Zkontrolovat, zda produkt již existuje
        $product = $this->productRepository->findByFeedAndItemId($feed, $data->itemId);

        if ($product) {
            // Update existujícího produktu
            $this->updateProduct($product, $data);
            $stats['updated']++;
        } else {
            // Vytvoření nového produktu
            $product = $this->createProduct($feed, $data);
            $this->entityManager->persist($product);
            $stats['created']++;
        }
    }

    private function createProduct(HeurekaFeed $feed, DTO\ProductDataDTO $data): Product
    {
        $product = new Product(
            $feed,
            $data->itemId,
            $data->productName,
            $this->normalizePrice($data->priceVat),
            $data->url
        );

        $this->populateProductFields($product, $data);

        return $product;
    }

    private function updateProduct(Product $product, DTO\ProductDataDTO $data): void
    {
        $product->setProductName($data->productName);
        $product->setPriceVat($this->normalizePrice($data->priceVat));
        $product->setUrl($data->url);

        $this->populateProductFields($product, $data);
    }

    private function populateProductFields(Product $product, DTO\ProductDataDTO $data): void
    {
        // Základní pole
        if ($data->description) {
            $product->setDescription($data->description);
        }

        if ($data->imgUrl) {
            $product->setImgUrl($data->imgUrl);
        }

        if ($data->imgUrlAlternative) {
            $product->setImgUrlAlternative($data->imgUrlAlternative);
        }

        if ($data->manufacturer) {
            $product->setManufacturer($data->manufacturer);
        }

        if ($data->ean) {
            $product->setEan($data->ean);
        }

        if ($data->productNo) {
            $product->setProductNo($data->productNo);
        }

        // Zpracování kategorie
        if ($data->categoryText) {
            $category = $this->processCategoryText($data->categoryText);
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
