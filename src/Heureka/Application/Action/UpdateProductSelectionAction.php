<?php

declare(strict_types=1);

namespace App\Heureka\Application\Action;

final readonly class UpdateProductSelectionAction
{
    public function __construct(
        public string $feedId,
        public array $selectedProductIds,
    ) {}
}
