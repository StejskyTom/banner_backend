<?php

namespace App\Heureka\Domain\Service\Parser;

use App\Heureka\Domain\DTO\ProductDataDTO;

class HeurekaParser implements FeedParserInterface
{
    public function parse(\SimpleXMLElement $xml): iterable
    {
        foreach ($xml->SHOPITEM as $shopItem) {
            yield $this->parseItem($shopItem);
        }
    }

    public function supports(\SimpleXMLElement $xml): bool
    {
        return $xml->getName() === 'SHOP' && isset($xml->SHOPITEM);
    }

    private function parseItem(\SimpleXMLElement $shopItem): ProductDataDTO
    {
        return new ProductDataDTO(
            itemId: (string) $shopItem->ITEM_ID,
            productName: (string) $shopItem->PRODUCTNAME,
            priceVat: (string) $shopItem->PRICE_VAT,
            url: (string) $shopItem->URL,
            description: isset($shopItem->DESCRIPTION) ? (string) $shopItem->DESCRIPTION : null,
            imgUrl: isset($shopItem->IMGURL) ? (string) $shopItem->IMGURL : null,
            imgUrlAlternative: isset($shopItem->IMGURL_ALTERNATIVE) ? (string) $shopItem->IMGURL_ALTERNATIVE : null,
            manufacturer: isset($shopItem->MANUFACTURER) ? (string) $shopItem->MANUFACTURER : null,
            ean: isset($shopItem->EAN) ? (string) $shopItem->EAN : null,
            productNo: isset($shopItem->PRODUCTNO) ? (string) $shopItem->PRODUCTNO : null,
            categoryText: isset($shopItem->CATEGORYTEXT) ? (string) $shopItem->CATEGORYTEXT : null
        );
    }
}
