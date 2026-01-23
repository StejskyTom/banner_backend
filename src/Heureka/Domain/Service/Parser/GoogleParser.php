<?php

namespace App\Heureka\Domain\Service\Parser;

use App\Heureka\Domain\DTO\ProductDataDTO;

class GoogleParser implements FeedParserInterface
{
    public function parse(\SimpleXMLElement $xml): iterable
    {
        // Register Google namespace
        $namespaces = $xml->getNamespaces(true);
        $gNamespace = $namespaces['g'] ?? 'http://base.google.com/ns/1.0';
        
        // Iterate through items
        foreach ($xml->channel->item as $item) {
            yield $this->parseItem($item, $gNamespace);
        }
    }

    public function supports(\SimpleXMLElement $xml): bool
    {
        // Google feeds are usually RSS 2.0 with a channel and items
        // We can check for 'rss' root element or 'channel' child
        return ($xml->getName() === 'rss' || $xml->getName() === 'feed') && isset($xml->channel->item);
    }

    private function parseItem(\SimpleXMLElement $item, string $namespace): ProductDataDTO
    {
        $g = $item->children($namespace);
        
        // Map Google fields to DTO
        // ID: g:id
        // Name: title
        // Price: g:price
        // URL: link
        // Description: description
        // Image: g:image_link
        
        $price = (string) $g->price; // content like "123.45 CZK"
        $price = preg_replace('/[^0-9.,]/', '', $price); // Keep only numbers, dots, commas

        return new ProductDataDTO(
            itemId: (string) $g->id,
            productName: (string) $item->title,
            priceVat: $price,
            url: (string) $item->link,
            description: (string) $item->description,
            imgUrl: (string) $g->image_link,
            imgUrlAlternative: isset($g->additional_image_link) ? (string) $g->additional_image_link : null,
            manufacturer: isset($g->brand) ? (string) $g->brand : null,
            ean: isset($g->gtin) ? (string) $g->gtin : null,
            productNo: isset($g->mpn) ? (string) $g->mpn : null,
            categoryText: isset($g->google_product_category) ? (string) $g->google_product_category : null
        );
    }
}
