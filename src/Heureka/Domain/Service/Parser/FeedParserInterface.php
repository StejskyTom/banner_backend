<?php

namespace App\Heureka\Domain\Service\Parser;

use App\Heureka\Domain\DTO\ProductDataDTO;

interface FeedParserInterface
{
    /**
     * @return iterable<ProductDataDTO>
     */
    public function parse(\SimpleXMLElement $xml): iterable;

    public function supports(\SimpleXMLElement $xml): bool;
}
