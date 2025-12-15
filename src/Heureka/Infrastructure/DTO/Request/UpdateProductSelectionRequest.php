<?php

namespace App\Heureka\Infrastructure\DTO\Request;

use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Type;

class UpdateProductSelectionRequest
{
    #[NotBlank(message: 'Seznam vybraných produktů je povinný')]
    #[Type('array')]
    public array $selectedProductIds = [];
}
