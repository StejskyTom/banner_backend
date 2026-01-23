<?php

namespace App\Heureka\Domain\DTO;

class ProductDataDTO
{
    public function __construct(
        public string $itemId,
        public string $productName,
        public string $priceVat,
        public string $url,
        public ?string $description = null,
        public ?string $imgUrl = null,
        public ?string $imgUrlAlternative = null,
        public ?string $manufacturer = null,
        public ?string $ean = null,
        public ?string $productNo = null,
        public ?string $categoryText = null,
    ) {}
}
